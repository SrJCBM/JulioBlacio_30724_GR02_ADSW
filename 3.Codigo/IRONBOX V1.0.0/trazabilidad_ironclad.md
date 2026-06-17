# IronClad Box - Trazabilidad Técnica

Documento de estado del proyecto **IronClad Box V2**, sistema web para gestión operativa de un gimnasio CrossFit. Resume arquitectura, módulos implementados, requerimientos cubiertos, datos demo y preparación de despliegue.

## Arquitectura y Stack Tecnológico

| Área | Decisión técnica |
| --- | --- |
| Frontend | HTML5, CSS3 y JavaScript puro |
| Backend | PHP nativo |
| Acceso a datos | PDO |
| Base activa | MySQL relacional |
| Scripts de base | `database/schema_mysql.sql` y `database/seed_mysql.sql` |
| Arquitectura | Capas con MVC |
| Patrón | Builder en lógica de negocio |
| Autenticación | `AuthController.php` + `includes/Auth.php` + sesión PHP |
| Navegación | Dashboards por rol y `assets/js/nav.js` en presentación |

## Estructura del Código

```text
3.Codigo/IRONBOX V1.0.0/
```

| Capa | Ruta |
| --- | --- |
| Presentación | `views/`, `assets/css/`, `assets/js/` |
| Controladores | `controllers/` |
| Servicios | `services/` |
| Builders | `builders/` |
| DAO | `dao/` |
| Entidades | `models/` |
| Sesión/conexión/utilidades | `includes/` |
| Base de datos | `database/` |

## Estado V2: Login, Correo, Cédula y Roles

- El sistema usa login con **correo + contraseña**.
- La entidad `Usuario` usa el atributo `correo`.
- El campo `cedula` se valida con algoritmo ecuatoriano.
- Se valida unicidad de correo y cédula.
- Las contraseñas se protegen con `password_hash`.
- La navegación visible se filtra por rol:
  - **Administrador:** usuarios, clases, membresías y reportes.
  - **Entrenador:** clases.
  - **Atleta:** clases/reservas y membresía personal.
- Los controladores críticos validan sesión/rol con `Auth.php`.

## Base MySQL y Datos Demo

Archivos:

- `database/schema_mysql.sql`: crea tablas, índices y claves foráneas.
- `database/seed_mysql.sql`: carga usuarios, atletas, entrenadores, membresías, clases, reservas, progreso y mensajes.

Datos relevantes:

- `Julio Blacio` fue eliminado y no está incluido en el seed MySQL.
- Las clases demo empiezan desde el `2026-06-18`.
- Se incluyen atletas con membresías `Pagado`, `Pendiente` y `Vencido` para probar bloqueos.
- Se incluyen reservas demo sin romper cupos disponibles.

## Diagramas Vigentes

| Diagrama | Archivo |
| --- | --- |
| Arquitectura por capas V2 | `2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml` |
| Clases V2 | `2.Diseños/2.3_Diagrama_de_Clases/diagramadeclasesv2.puml` |

## Módulos Completados - Release 1

### [x] Módulo 1: Gestión de Agenda de Clases e Inscripciones

Archivos principales: `Clase.php`, `Reserva.php`, `ClaseBuilder.php`, `ClaseService.php`, `ClaseDAO.php`, `ClaseController.php`, `gestion_clases.html`, `reservas_atleta.html`, `app.js`, `reservas.js`.

Reglas cubiertas:

- Crear, editar, listar y eliminar clases.
- Validar día, hora, duración, cupo y entrenador.
- Validar disponibilidad de entrenador.
- Evitar solapamiento de horarios.
- Controlar cupo máximo y cupos disponibles.
- Reservar y cancelar cupos desde el perfil atleta.
- Validar membresía vigente antes de reservar.
- Evitar reservas activas duplicadas.
- Restar/liberar cupos en transacción.

Mapeo V4:

- **REQ013:** creación de clases mediante formulario, `ClaseBuilder`, `ClaseService` y `ClaseDAO`.
- **REQ014:** edición de clases mediante controlador, servicio y DAO.
- **REQ015:** eliminación de clases desde la vista y persistencia DAO.
- **REQ016:** asignación obligatoria de entrenador.
- **REQ017:** control de cupos con `ClaseBuilder` y reservas.
- **REQ018:** validación de horarios, solapamientos y disponibilidad.

### [x] Módulo 2: Gestión de Membresías y Pagos

Archivos principales: `Membresia.php`, `MembresiaBuilder.php`, `MembresiaService.php`, `MembresiaDAO.php`, `MembresiaController.php`, `gestion_membresias.html`, `membresias.js`.

Reglas cubiertas:

- Crear, editar y asignar membresías.
- Registrar pagos.
- Cambiar estado a `Pagado`.
- Calcular vencimiento automático a 30 días.
- Consultar estado y vencimiento.
- Bloquear beneficios si la membresía no está vigente.

Mapeo V4:

- **REQ007:** creación de membresías.
- **REQ008:** edición de membresías.
- **REQ009:** asignación a atletas.
- **REQ010:** registro de pagos.
- **REQ011:** consulta de membresías.
- **REQ012:** control de vencimientos.

### [x] Módulo 3: Gestión de Usuarios

Archivos principales: `Usuario.php`, `UsuarioBuilder.php`, `UsuarioService.php`, `UsuarioDAO.php`, `UsuarioController.php`, `AuthController.php`, `Auth.php`, `login.html`, `index.html`, `gestion_usuarios.html`, `login.js`, `nav.js`, `usuarios.js`.

Reglas cubiertas:

- Crear, editar, listar y desactivar usuarios.
- Validar cédula ecuatoriana.
- Validar correo.
- Bloquear correos y cédulas duplicadas.
- Hashear contraseña.
- Aplicar baja lógica con estado `Inactivo`.
- Autenticar usuarios activos.
- Renderizar navegación según rol.

Mapeo V4:

- **REQ001:** creación de cuentas.
- **REQ002:** edición de cuentas.
- **REQ003:** desactivación de cuentas.
- **REQ004:** roles como catálogo fijo validado.
- **REQ005:** asignación de rol desde usuario.
- **REQ006:** permisos derivados del rol y aplicados por vistas/controladores.

### [x] Módulo 4: Seguimiento de Progreso Deportivo - Entrenador

Archivos principales: `RegistroProgreso.php`, `RegistroProgresoBuilder.php`, `ProgresoService.php`, `ProgresoDAO.php`, `ProgresoController.php`, `seguimiento_progreso.html`, `progreso.js`.

Reglas cubiertas:

- Registrar resultados WOD por atleta.
- Permitir tiempo, repeticiones, peso, puntuación y notas como campos opcionales.
- Exigir fecha e id de atleta.
- Impedir registros deportivos vacíos.
- Consultar historial ordenado por fecha.

### [x] Módulo 5: Reservas de Clases - Atleta

Este módulo quedó consolidado dentro de `ClaseService` y `ClaseDAO`, porque la reserva afecta directamente la agenda y los cupos.

Reglas cubiertas:

- Validar membresía pagada y no vencida.
- Consultar cupos disponibles.
- Crear reserva confirmada.
- Cancelar reserva y liberar cupo.
- Prevenir duplicados activos.

### [x] Módulo 6: Registro de Progreso Personal - Atleta

Reutiliza `RegistroProgreso.php`, `RegistroProgresoBuilder.php`, `ProgresoService.php` y `ProgresoDAO.php`.

Reglas cubiertas:

- Registrar entrenamiento manual.
- Consultar historial propio.
- Usar id de atleta por sesión o parámetro.
- Mantener placeholder de integración con apps de salud.

### [x] Módulo 7: Gestión de Membresía Personal - Atleta

Reutiliza `Membresia.php`, `MembresiaBuilder.php`, `MembresiaService.php` y `MembresiaDAO.php`.

Reglas cubiertas:

- Consultar membresía propia.
- Simular pago en línea.
- Renovar con la misma lógica administrativa.
- Cancelar membresía.
- Extender vencimiento a 30 días al pagar.

### [x] REQ004-3: Gráficos de Progreso

Reglas cubiertas:

- Endpoint `obtenerDatosGrafico`.
- JSON con fechas, puntuaciones y pesos.
- Gráfico de evolución con Chart.js por CDN.
- Actualización dinámica al seleccionar atleta.

## Módulos Completados - Release 2

### [x] Comunicación entre Entrenadores y Atletas

Archivos principales: `Mensaje.php`, `ComunicacionService.php`, `ComunicacionDAO.php`, `ComunicacionController.php`, `comunicacion_entrenador.html`, `bandeja_atleta.html`.

Reglas cubiertas:

- Enviar mensajes individuales.
- Enviar anuncios generales.
- Validar remitente entrenador y destinatario atleta.
- Consultar historial enviado.
- Consultar bandeja de entrada del atleta.

### [x] Reportes Administrativos

Archivos principales: `Reporte.php`, `ReporteBuilder.php`, `ReporteService.php`, `ReporteDAO.php`, `ReporteController.php`, `reportes_admin.html`, `reportes_admin.js`.

Reglas cubiertas:

- Reportes de finanzas y asistencia.
- Filtros por fechas.
- Consultas consolidadas en DAO.
- Construcción con `ReporteBuilder`.
- Visualización en tabla.
- Exportación CSV.
- Preparación de salida PDF mediante impresión del navegador.

## Preparación de Despliegue

- Conexión central MySQL en `includes/Database.php`.
- Variables de entorno en `.env.example`.
- Dockerfile para Render.
- `render.yaml` como base de despliegue.
- Guía específica en `DEPLOY_RENDER_AZURE.md`.
- Recomendación: Render con Docker para la app y Azure Database for MySQL Flexible Server para datos.

## Trabajo en Progreso

No quedan funcionalidades pendientes dentro del alcance documentado. La fase actual es:

```text
Revisión final, pruebas integrales y preparación de commit/despliegue.
```

## Validaciones Técnicas

- Sintaxis PHP con `php -l`.
- Sintaxis JavaScript con `node --check`.
- Revisión manual de rutas, vistas y controladores.
- Revisión de UML V2 contra implementación real.
- Limpieza de referencias antiguas a la base local previa.

## Nota Pre-Commit

La documentación, los UML V2 y el código quedan alineados en los puntos clave:

- `correo` como atributo principal de usuario.
- `cedula` validada en usuarios.
- Login por rol con `AuthController` y `Auth.php`.
- `nav.js` documentado como implementación de presentación.
- Builder aplicado solo en entidades complejas.
- MySQL/PDO como base activa preparada para despliegue.
