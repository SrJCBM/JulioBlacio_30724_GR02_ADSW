# IronClad Box

Sistema web MVP para la gestión operativa de un gimnasio CrossFit. El proyecto implementa flujos para tres actores principales: **Administrador**, **Entrenador** y **Atleta**, usando arquitectura por capas con **MVC**, **PHP nativo**, **Vanilla JS**, **PDO** y **MySQL**.

## Estado actual

El repositorio contiene el desarrollo funcional de **IronClad Box V2**, incluyendo:

- Login con correo y contraseña.
- Navegación por rol autenticado.
- Validación de cédula ecuatoriana en usuarios.
- Gestión de usuarios, clases, membresías, reservas, progreso, comunicación y reportes.
- Scripts MySQL para crear y sembrar la base.
- Diagramas UML V2 alineados con la implementación real.

## Ubicación del código

```text
3.Codigo/IRONBOX V1.0.0/
```

Estructura principal:

```text
assets/       CSS y JavaScript Vanilla
builders/     Implementaciones del patrón Builder
controllers/  Controladores MVC en PHP
dao/          Acceso a datos con PDO
database/     Scripts MySQL de esquema y datos demo
includes/     Autenticación, sesión y utilidades compartidas
models/       Entidades de dominio
services/     Lógica de negocio
views/        Vistas HTML
```

## Base de datos MySQL

Importar primero el esquema y luego los datos demo:

```bash
mysql -h <host> -P <puerto> -u <usuario> -p < "3.Codigo/IRONBOX V1.0.0/database/schema_mysql.sql"
mysql -h <host> -P <puerto> -u <usuario> -p ironclad_box < "3.Codigo/IRONBOX V1.0.0/database/seed_mysql.sql"
```

Configurar variables de entorno tomando como referencia:

```text
3.Codigo/IRONBOX V1.0.0/.env.example
```

## Ejecución rápida

Desde la raíz del repositorio:

```bash
cd "3.Codigo/IRONBOX V1.0.0"
php -S localhost:8000
```

Abrir en el navegador:

```text
http://localhost:8000/views/login.html
```

## Credenciales demo

Todas usan la contraseña:

```text
IronClad123
```

| Rol | Correo | Cédula |
| --- | --- | --- |
| Administrador | `admin@ironcladbox.local` | `0706499860` |
| Entrenador | `valeria.rios@ironcladbox.local` | `0801308321` |
| Atleta | `daniela.moya@ironcladbox.local` | `1725916645` |

## Rutas principales

| Vista | Ruta |
| --- | --- |
| Login | `/views/login.html` |
| Dashboard | `/views/index.html` |
| Usuarios | `/views/gestion_usuarios.html` |
| Clases | `/views/gestion_clases.html` |
| Membresías | `/views/gestion_membresias.html` |
| Reservas del atleta | `/views/reservas_atleta.html` |
| Membresía del atleta | `/views/membresia_atleta.html` |
| Progreso del atleta | `/views/progreso_atleta.html` |
| Seguimiento entrenador | `/views/seguimiento_progreso.html` |
| Comunicación entrenador | `/views/comunicacion_entrenador.html` |
| Bandeja atleta | `/views/bandeja_atleta.html` |
| Reportes | `/views/reportes_admin.html` |

## Despliegue recomendado

- **App PHP:** Render usando Docker, o Azure App Service si se desea centralizar todo.
- **Base de datos:** Azure Database for MySQL Flexible Server.
- **Conexión:** configurar `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` y `DB_CHARSET` en el entorno del servicio.

## Documentación técnica

- Guía de defensa: `3.Codigo/IRONBOX V1.0.0/GUIA_TECNICA_Y_DEFENSA.md`
- Trazabilidad: `3.Codigo/IRONBOX V1.0.0/trazabilidad_ironclad.md`
- Patrón Builder: `3.Codigo/IRONBOX V1.0.0/PATRON_BUILDER.md`
- Arquitectura V2: `2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml`
- Diagrama de clases V2: `2.Diseños/2.3_Diagrama_de_Clases/diagramadeclasesv2.puml`
