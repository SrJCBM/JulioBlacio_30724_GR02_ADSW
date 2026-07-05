# Autoasignación de roles y mejora del flujo de membresía

**Fecha:** 2026-07-05
**Alcance:** cambios 1, 2, 3a, 3b y 3c, más los ajustes de diseño/usabilidad detectados en la revisión visual.

## Contexto

IRONBOX es una app PHP por capas (Controller → Service → DAO → Builder → Model), sin framework, con sesiones. Detalle arquitectónico clave: `usuarios`, `atletas` y `entrenadores` son **tablas separadas**, sincronizadas por correo en `UsuarioDAO::sincronizarPersonaPorRol`. Por eso:

- `clase.entrenadorId` referencia `entrenadores.id`, **no** `usuarios.id`.
- La membresía y el progreso referencian `atletas.id`, **no** `usuarios.id`.
- La sesión guarda `$_SESSION['usuario']` (fila de `usuarios`) y, solo para rol Atleta, `$_SESSION['id_atleta']` (resuelto por correo en login). **No** existe `$_SESSION['id_entrenador']`.

## Cambio 1 — El entrenador se autoasigna al crear clase

**Problema:** el formulario de clase lista *todos* los entrenadores en un `<select>`; un entrenador puede asignar la clase a otro.

**Solución:**

1. **Sesión** (`controllers/AuthController.php`): añadir `resolverIdEntrenadorSesion($usuario)` (espejo de `resolverIdAtletaSesion`). Para rol Entrenador, resolver `entrenadores.id` por correo y guardar `$_SESSION['id_entrenador']`. Requiere un método DAO `buscarEntrenadorPorCorreo(correo)` (en `ClaseDAO` o `MembresiaDAO`, siguiendo el patrón de `buscarAtletaPorCorreo`).
2. **Autoridad en servidor** (`controllers/ClaseController.php`, acciones `crear` y `editar`): si `authUsuarioActual()['rol'] === 'Entrenador'`, sobrescribir `$payload['entrenadorId'] = (int) $_SESSION['id_entrenador']` **ignorando** cualquier valor del cliente. El Administrador conserva el valor enviado.
3. **Frontend** (`assets/js/app.js` + `views/gestion_clases.html`): detectar el rol vía `AuthController.php?action=me` (patrón ya usado en `reservas.js`).
   - Entrenador → ocultar el campo `<select id="entrenadorId">` (y su `required`), no llamar `cargarEntrenadores()`.
   - Administrador → comportamiento actual (dropdown con todos).

**Fuera de alcance:** no se cambia cómo el Admin elige entrenador.

## Cambio 2 — "Mi progreso" se fija al atleta logueado

**Problema:** `progreso_atleta.html` (vista del Atleta) muestra un `<select>` con **todos** los atletas.

**Estado actual del servidor:** `ProgresoController::obtenerIdAtletaProgreso` ya fuerza `$_SESSION['id_atleta']` para rol Atleta en `historial`, `historialAtleta`, `guardar`, `guardarAtleta`, `obtenerDatosGrafico`. El IDOR de lectura/escritura ya está mitigado. La única fuga es la acción `atletas`, sin restricción de rol.

**Solución:**

1. **Frontend** (`views/progreso_atleta.html` + `assets/js/progreso_atleta.js`): eliminar el `<select id="idAtleta">` y toda su carga (`cargarAtletas`, `preseleccionarAtletaDesdeUrl`). El registro y el historial usan la sesión (no se envía `idAtleta`; el servidor lo resuelve). El historial se carga directo al iniciar.
2. **Endurecimiento** (`controllers/ProgresoController.php`, acción `atletas`): restringir a `['Administrador', 'Entrenador']` para no exponer la lista completa a un Atleta.

**Nota:** `seguimiento_progreso.html` + `progreso.js` (vista del Entrenador) **no cambian**: ahí el selector de atleta es correcto.

## Cambio 3a — Asignar membresía al crear usuario (Admin)

**Problema:** un usuario Atleta recién creado no puede reservar hasta que el admin le asigne una membresía en otra pantalla (pasos 3–5 del caso reportado).

**Solución:**

1. **Frontend** (`views/gestion_usuarios.html` + `assets/js/usuarios.js`): en el drawer de creación, añadir un fieldset **opcional** de membresía (tipo, precio, fecha inicio, estado `Pendiente`/`Pagado`), visible solo cuando `rol = Atleta` (toggle al cambiar el select de rol). Solo aplica en creación, no en edición.
2. **Servidor** (`controllers/UsuarioController.php`, acción `crear`): tras `$service->crear($payload)`, si el rol es Atleta y llegan datos de membresía, resolver el `atletas.id` por correo (`MembresiaDAO::buscarAtletaPorCorreo`) y llamar `MembresiaService::crear([...])`. La orquestación cruza módulos pero se hace en el controlador (ambos requieren rol Administrador; se llama al Service directo, no vía HTTP).
3. **Manejo de error:** si la creación de la membresía falla, el usuario ya quedó creado. El mensaje debe dejarlo claro (p. ej. "Usuario creado, pero no se pudo asignar la membresía: <detalle>") para que el admin la complete manualmente.

**Decisión:** la membresía es opcional. Si el admin no llena el fieldset, el usuario se crea sin membresía (comportamiento actual).

## Cambio 3b — Bloqueo de reserva accionable

**Problema:** al reservar sin membresía vigente, el servidor lanza `DomainException('El atleta no tiene una membresia pagada y vigente.')` (422) y `reservas.js` lo muestra como un `status` de error genérico.

**Solución** (`views/reservas_atleta.html` + `assets/js/reservas.js`):

1. Al cargar el panel del atleta, consultar su membresía con `MembresiaController.php?action=miMembresia` (ya scoped a sesión para rol Atleta).
2. Mostrar un **banner de estado** arriba del panel:
   - Sin membresía / vencida / no pagada → banner `warn` claro: "No tienes una membresía activa. Contacta al administrador para activarla." (En la fase 3c este banner ganará el botón "Solicitar membresía".)
   - Membresía vigente → sin banner (o banner discreto con tipo y vencimiento).
3. Al fallar una reserva por el 422 de membresía, dirigir el mensaje al mismo banner accionable en vez del `status` genérico.

**Server:** sin cambios (la validación de reserva ya existe en `ClaseService::reservar`).

## Cambio 3c — Atleta solicita membresía (autoservicio)

Decisiones: modelo = **membresía Pendiente** (reutiliza la entidad existente, sin tablas nuevas); ubicación del CTA = **banner de reservas**.

1. **Servidor** — `MembresiaController` acción `solicitar` (solo Atleta, POST, scoped a sesión). `MembresiaService::solicitar($idAtleta)`: guard anti-duplicado (si ya hay una **Pendiente** o una **Pagada vigente**, lanza error); si no, crea una membresía `tipo="Por definir"`, `precio=0`, `fechaInicio=hoy`, `estado="Pendiente"` reutilizando `crear()`.
2. **Frontend** (`reservas.js` + `.banner`) — el banner ámbar gana un botón **"Solicitar membresía"** (solo sesión atleta, solo cuando no hay membresía ni solicitud). Estados: sin membresía/vencida → ámbar + botón; **Pendiente** → ámbar "solicitud pendiente de aprobación" (sin botón); vigente → verde. Al enviar, recarga el panel. `.banner` pasa a flex (texto izquierda, CTA derecha).
3. **Admin (sin cambios de código)** — la membresía Pendiente aparece en la gestión de membresías; el admin la edita (plan + precio reales) y registra el pago → Pagada → el atleta puede reservar.

**Ciclo resultante:** atleta topa el bloqueo → "Solicitar" → banner "pendiente" → admin completa plan+pago → atleta reserva. Sin contactos manuales fuera de la app.

## Mejoras de diseño/usabilidad detectadas en revisión visual

- **Bug `[hidden]`**: la regla `button/.primary { display:inline-flex }` anulaba el atributo `hidden` → botones ocultos se mostraban ("Cancelar edición" al crear). Se añadió guard `[hidden] { display:none !important }`.
- **`:disabled`**: no existía estilo → botones deshabilitados se veían activos. Se añadió atenuación.
- **Reservas — fuga + UI**: para la sesión atleta ya no se pide la lista completa de atletas (mismo leak que el cambio 2) y se oculta el panel selector; "Actualizar" se movió a la cabecera de clases y `statusMessage` a nivel de `main`.
- **Reservas — botones**: "Reservar" se deshabilita cuando no hay membresía vigente (el banner se resuelve antes de pintar).
- **Claridad**: "Estado" del fieldset de membresía → "Estado del pago".

## Verificación

- **Cambio 1:** con sesión Entrenador, crear clase → queda asignada a sí mismo; el `<select>` no aparece. Con sesión Admin, el dropdown sigue presente y funcional. Un Entrenador no puede crear/editar una clase con `entrenadorId` de otro (verificar server-side).
- **Cambio 2:** con sesión Atleta, "Mi progreso" no muestra selector y solo registra/muestra su propio progreso. La acción `atletas` responde 403 a un Atleta.
- **Cambio 3a:** crear un Atleta con membresía en un paso → puede reservar sin intervención adicional. Sin fieldset → usuario sin membresía (igual que hoy).
- **Cambio 3b:** un Atleta sin membresía ve el banner claro; al intentar reservar, el mensaje va al banner, no a un error genérico.

## Restricciones que se mantienen

- No rotar la contraseña real de la BD (paso manual del usuario en Aiven).
- No commitear/pushear ni desplegar (lo hace el usuario). No dejar vistas a medias entre commits.
- Mantener contratos JS (IDs de elementos, lectura de `idAtleta` por `getElementById`, no por `FormData`).
- Cache-busting `?v=N` al tocar assets.
