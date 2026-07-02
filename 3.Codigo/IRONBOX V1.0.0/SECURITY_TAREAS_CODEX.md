# Tareas de seguridad para Codex — IRONBOX V1.0.0

Specs autocontenidas para pegar en Codex. Cada tarea es independiente.
El fix de autenticación + IDOR (findings 1-2) lo está haciendo Opus por separado; no lo dupliques aquí.

---

## Tarea C1 — ALTO: Secretos y .gitignore

**Problema:** `.env` contiene credenciales reales de MySQL (Aiven) y no existe `.gitignore`.
`data/ironclad_box.sqlite` está trackeado en git.

**Cambios:**
1. Crear `.gitignore` en la raíz del proyecto con al menos:
   ```
   .env
   *.sqlite
   ca.pem
   /data/*.sqlite
   ```
2. Sacar del control de versiones sin borrar el archivo local:
   ```
   git rm --cached data/ironclad_box.sqlite
   ```
3. Crear `.env.example` con las MISMAS claves pero valores vacíos/placeholder
   (DB_HOST=, DB_PORT=, DB_PASSWORD=, etc.) para documentar el formato.

**Nota manual (NO automatizar):** rotar la contraseña `DB_PASSWORD` en el panel de Aiven,
porque ya estuvo en texto plano en el árbol de trabajo. Codex no debe intentar esto.

**Verificación:** `git status` no debe listar `.env`; `git ls-files` no debe incluir el `.sqlite`.

---

## Tarea C2 — MEDIO: CORS restrictivo

**Problema:** todos los controllers en `controllers/*.php` envían
`header('Access-Control-Allow-Origin: *')` en endpoints con sesión.

**Cambio:** reemplazar el `*` por una whitelist de orígenes leída de entorno.
Sugerencia: crear un helper en `includes/` (p.ej. `Cors.php`) con una función
`aplicarCors(): void` que:
- lea `APP_ALLOWED_ORIGINS` de `.env` (lista separada por comas),
- compare contra `$_SERVER['HTTP_ORIGIN']`,
- si coincide, emita `Access-Control-Allow-Origin: <ese origin>` y
  `Access-Control-Allow-Credentials: true`,
- mantenga `Allow-Methods`/`Allow-Headers` como están.

Luego sustituir las 3 líneas de headers CORS en cada controller por `aplicarCors();`
(requiriendo el helper). Añadir `APP_ALLOWED_ORIGINS` a `.env` y `.env.example`.

**Archivos:** los 7 controllers en `controllers/` + nuevo `includes/Cors.php`.

**Verificación:** una petición con `Origin` no listado NO debe recibir el header de allow-origin.

---

## Tarea C3 — MEDIO/BAJO: Robustez de login

**Archivo:** `services/UsuarioService.php` (método `autenticar`, línea ~96)
y `builders/UsuarioBuilder.php` (método `definirContrasena`, línea 62).

**Cambios:**
1. Subir la longitud mínima de contraseña de 6 a 8 en `UsuarioBuilder::definirContrasena`.
2. Añadir throttling básico de intentos fallidos de login por correo/IP.
   Opción simple sin nueva tabla: contador en sesión + `sleep` progresivo,
   o mejor: tabla `intentos_login (correo, ip, ts)` y bloquear tras 5 fallos en 15 min.
   Implementar en `AuthController.php` (acción `login`) o en `UsuarioService::autenticar`.

**Verificación:** 6 intentos fallidos seguidos deben devolver 429/bloqueo temporal.
La contraseña de 7 caracteres debe ser rechazada al crear usuario.

---

## Contexto del proyecto
- PHP puro, patrón DAO/Service/Builder, PDO/MySQL (sentencias preparadas — SQL injection ya mitigado).
- Auth mediante sesiones PHP; helpers en `includes/Auth.php`
  (`authRequerirSesion`, `authRequerirRol`).
- No hay tests automatizados; verificar manualmente con peticiones HTTP.
