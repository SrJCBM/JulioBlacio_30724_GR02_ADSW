# Despliegue: Render + Azure MySQL

Esta guía resume la preparación del prototipo para ejecutarse con **MySQL** y publicarse fuera del entorno local.

## Opción recomendada

- **Aplicación PHP:** Render usando Docker.
- **Base de datos:** Azure Database for MySQL Flexible Server.

Esta combinación permite publicar la aplicación en Render y usar los créditos de Azure para una base MySQL administrada.

## Variables de entorno

Configurar en Render o Azure App Service:

```text
DB_HOST=<host de MySQL>
DB_PORT=3306
DB_NAME=ironclad_box
DB_USER=<usuario>
DB_PASSWORD=<contraseña>
DB_CHARSET=utf8mb4
```

Si Azure exige certificado SSL, agregar:

```text
DB_SSL_CA=/ruta/al/certificado.pem
```

## Crear base de datos

Ejecutar primero el esquema:

```bash
mysql -h <host> -P 3306 -u <usuario> -p < database/schema_mysql.sql
```

Luego sembrar datos demo:

```bash
mysql -h <host> -P 3306 -u <usuario> -p ironclad_box < database/seed_mysql.sql
```

## Docker

El `Dockerfile` de la raíz usa PHP con Apache y habilita `pdo_mysql`. Render puede construir la imagen directamente desde el repositorio.

## Datos demo

El seed incluye:

- Usuarios demo de administrador, entrenador y atleta.
- Entrenadores y atletas adicionales.
- Membresías con estados `Pagado`, `Pendiente` y `Vencido`.
- Clases desde el `2026-06-18` en adelante.
- Reservas demo sin romper cupos.
- Mensajes y progreso deportivo básico.

El usuario `Julio Blacio` no está incluido en el seed MySQL.

## Recomendaciones

- Usar Azure MySQL para datos persistentes y backups administrados.
- Mantener las credenciales solo en variables de entorno.
- No subir archivos `.env` al repositorio.
- Verificar que Render pueda conectarse al host MySQL de Azure mediante firewall/reglas de red.
