# Guía Técnica y de Defensa - IronClad Box

## 1. Introducción al Sistema

**IronClad Box** es un sistema web MVP para la gestión operativa de un gimnasio CrossFit. Cubre los procesos principales de tres actores:

- **Administrador:** gestiona usuarios, clases, membresías, pagos y reportes.
- **Entrenador:** gestiona clases según el alcance del prototipo, registra progreso deportivo y se comunica con atletas.
- **Atleta:** consulta clases, reserva cupos, revisa su membresía, registra progreso personal y lee mensajes.

El proyecto evolucionó a **IronClad Box V2**, incorporando login por rol, validación de cédula ecuatoriana, navegación dinámica, comunicación interna, reportes administrativos y preparación para despliegue con **MySQL**.

## 2. Arquitectura y Stack Tecnológico

El sistema usa **PHP nativo** en backend y **HTML, CSS y Vanilla JS** en frontend. No se usan frameworks como Laravel, React, Angular o Bootstrap. Esta decisión permite defender directamente el flujo HTTP, la separación por capas y la implementación del patrón **MVC**.

La implementación real está en:

```text
3.Codigo/IRONBOX V1.0.0/
```

Capas principales:

- **View:** HTML, CSS y JavaScript en `views/` y `assets/`.
- **Controller:** endpoints PHP en `controllers/`.
- **Service / Builder:** lógica de negocio y construcción de entidades en `services/` y `builders/`.
- **DAO / Repository:** acceso a datos con **PDO** en `dao/`.
- **Models:** entidades de dominio en `models/`.
- **Includes:** sesión, autenticación, conexión y utilidades compartidas en `includes/`.

### Login, sesión y navegación por rol

El login inicia en:

```text
http://localhost:8000/views/login.html
```

Flujo técnico:

```text
LoginView -> AuthController -> UsuarioService -> UsuarioDAO -> Auth.php / $_SESSION
```

Después del login, el usuario entra al dashboard:

```text
http://localhost:8000/views/index.html
```

`assets/js/nav.js` pertenece a la **capa de presentación**. Consulta `AuthController.php?action=me` y renderiza opciones según el rol:

- **Administrador:** usuarios, clases, membresías y reportes.
- **Entrenador:** clases.
- **Atleta:** clases/reservas y membresía personal.

Los controladores críticos también validan sesión y rol con `includes/Auth.php`, evitando acceso directo por URL a acciones no permitidas.

### Patrón Creacional Builder

El patrón **Builder** se usa en la capa de lógica de negocio para construir entidades complejas paso a paso con validaciones intermedias:

- `UsuarioBuilder`: valida nombre, cédula ecuatoriana, correo, rol, estado y contraseña hasheada.
- `ClaseBuilder`: evita clases sin día, hora, duración, cupo o entrenador.
- `MembresiaBuilder`: valida tipo, precio, estado y calcula vencimientos a 30 días.
- `RegistroProgresoBuilder`: permite WODs con campos opcionales, pero bloquea registros vacíos.
- `ReporteBuilder`: arma reportes con filtros y formatos de salida sin duplicar lógica.

`Auth.php`, `nav.js` y `ComunicacionService` no usan Builder porque no construyen entidades complejas con validaciones progresivas.

## 3. Gestión de Base de Datos: MySQL y PDO

IronClad Box usa **MySQL** como base de datos activa. La conexión está centralizada en:

```text
3.Codigo/IRONBOX V1.0.0/includes/Database.php
```

El acceso a datos está encapsulado en clases DAO usando **PDO**. Esto permite que controladores y servicios no dependan directamente de sentencias SQL ni de credenciales.

Scripts principales:

```text
3.Codigo/IRONBOX V1.0.0/database/schema_mysql.sql
3.Codigo/IRONBOX V1.0.0/database/seed_mysql.sql
```

Variables de entorno:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
DB_CHARSET
DB_SSL_CA
```

Ejemplo de conexión:

```php
$dsn = 'mysql:host=localhost;port=3306;dbname=ironclad_box;charset=utf8mb4';
```

Para despliegue se recomienda **Azure Database for MySQL Flexible Server** como base administrada y Render con Docker para la aplicación PHP, o Azure App Service si se desea mantener todo en Azure.

## 4. Guía de Flujos Transaccionales

### Demostración Inicial: Login

1. Levantar el servidor con `php -S localhost:8000`.
2. Abrir `http://localhost:8000/views/login.html`.
3. Iniciar sesión con una cuenta demo.
4. Mostrar que el dashboard cambia sus opciones según el rol.

Credenciales demo:

| Rol | Correo | Contraseña | Cédula |
| --- | --- | --- | --- |
| Administrador | `admin@ironcladbox.local` | `IronClad123` | `0706499860` |
| Entrenador | `valeria.rios@ironcladbox.local` | `IronClad123` | `0801308321` |
| Atleta | `daniela.moya@ironcladbox.local` | `IronClad123` | `1725916645` |

### Demostración del Administrador

**Usuarios REQ001 a REQ006:** abrir `/views/gestion_usuarios.html`, crear usuarios, validar cédula ecuatoriana, editar datos, desactivar una cuenta y explicar roles/permisos por rol.

**Membresías REQ007 a REQ012:** abrir `/views/gestion_membresias.html`, asignar una membresía, registrar un pago y demostrar que REQ010 actualiza el estado a `Pagado` mientras REQ012 extiende el vencimiento a 30 días.

**Clases REQ013 a REQ018:** abrir `/views/gestion_clases.html`, crear una clase, validar cupo, asignar entrenador y demostrar bloqueo de horarios solapados.

**Reportes:** abrir `/views/reportes_admin.html`, seleccionar fechas y tipo de reporte, visualizar resultados y exportar CSV.

### Demostración del Entrenador

Abrir `/views/gestion_clases.html` para mostrar que el rol entrenador ve solo el módulo de clases.

Luego abrir `/views/seguimiento_progreso.html`, seleccionar un atleta, registrar un WOD con campos opcionales y mostrar historial/gráfico. `RegistroProgresoBuilder` permite flexibilidad por tipo de WOD, pero evita registros sin datos deportivos reales.

Para comunicación, abrir `/views/comunicacion_entrenador.html`, enviar un mensaje a un atleta o un anuncio general.

### Demostración del Atleta

Abrir `/views/reservas_atleta.html`, ver clases disponibles y reservar. Este es el flujo más fuerte del sistema porque cruza módulos: `ClaseService` valida cupos con `ClaseDAO` y membresía vigente con `MembresiaDAO`.

Luego abrir `/views/membresia_atleta.html` para consultar, renovar o cancelar membresía. Finalmente abrir `/views/bandeja_atleta.html` para leer mensajes enviados por el entrenador.

### Demostración Visual REQ004-3

Abrir `/views/progreso_atleta.html` o `/views/seguimiento_progreso.html`. El frontend consume:

```text
ProgresoController.php?action=obtenerDatosGrafico&idAtleta=1
```

El endpoint devuelve JSON con fechas, puntuaciones y pesos, y Chart.js renderiza la evolución deportiva.

## 5. Walkthrough: Cómo iniciar el sistema y navegar

### Preparar MySQL

```bash
mysql -h <host> -P <puerto> -u <usuario> -p < database/schema_mysql.sql
mysql -h <host> -P <puerto> -u <usuario> -p ironclad_box < database/seed_mysql.sql
```

Crear `.env` a partir de `.env.example`.

### Levantar servidor local

```bash
cd "3.Codigo/IRONBOX V1.0.0"
php -S localhost:8000
```

Abrir:

```text
http://localhost:8000/views/login.html
```

### Flujo recomendado de demostración

1. **Administrador:** crear usuarios, asignar membresía pagada y abrir clases.
2. **Atleta:** reservar una clase y consultar su membresía.
3. **Entrenador:** gestionar clases y registrar progreso.
4. **Visual:** mostrar gráfico de progreso con Chart.js.
5. **Comunicación:** enviar mensaje desde entrenador y leerlo desde atleta.
6. **Reportes:** volver como administrador y exportar CSV.

## 6. Cierre técnico

IronClad Box V2 queda defendible como prototipo funcional con:

- **MVC** para separar presentación, controladores, servicios y datos.
- **Builder** para construir entidades complejas de forma validada.
- **PDO + MySQL** para desacoplar y persistir datos.
- **Sesión PHP** para login y control de rol.
- **Vanilla JS** para consumo por `fetch` y renderizado dinámico.
- **Docker** como vía de despliegue en Render.
