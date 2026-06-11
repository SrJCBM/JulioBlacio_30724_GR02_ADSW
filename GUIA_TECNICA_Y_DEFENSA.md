# GUIA TECNICA Y DE DEFENSA - IronClad Box

## 1. Introduccion al Sistema

**IronClad Box** es un sistema web MVP para la gestion operativa de un gimnasio CrossFit. El Release 1 cubre los procesos principales de tres actores:

- **Administrador:** gestiona clases, usuarios, membresias y pagos.
- **Entrenador:** registra y consulta el progreso deportivo de los atletas.
- **Atleta:** reserva clases, consulta y renueva su membresia, registra su progreso personal y visualiza su evolucion deportiva.

El proyecto escala ahora al **Release 2**, incorporando un modulo de comunicacion interna para conectar entrenadores y atletas mediante mensajes directos y anuncios generales.

El sistema fue construido como un prototipo funcional de arquitectura limpia, con separacion estricta entre interfaz, controladores, logica de negocio y acceso a datos.

## 2. Arquitectura y Stack Tecnologico

El proyecto utiliza **PHP nativo** en backend y **Vanilla JS** en frontend. No se emplean frameworks como React, Angular, Laravel o Bootstrap. Esta decision permite demostrar de forma directa los principios de arquitectura, la separacion de responsabilidades y el flujo completo de peticiones HTTP sin depender de abstracciones externas.

### Arquitectura por Capas MVC

IronClad Box implementa una arquitectura por capas basada en **MVC**:

- **View:** archivos HTML, CSS y JavaScript ubicados en `3.Codigo/views` y `3.Codigo/assets/js`.
- **Controller:** endpoints PHP en `3.Codigo/controllers`, responsables de recibir peticiones `GET`/`POST`, normalizar entradas y devolver JSON.
- **Model / Services:** servicios en `3.Codigo/services`, donde vive la logica de negocio.
- **DAO / Repository:** clases en `3.Codigo/dao`, encargadas exclusivamente del acceso a datos mediante SQL y **PDO**.
- **Entities:** clases de dominio en `3.Codigo/models`.

### Patron Creacional Builder

Se implementa el patron **Builder** en la capa de logica de negocio para construir entidades complejas paso a paso con validaciones intermedias.

Ejemplos defendibles:

- `ClaseBuilder` evita crear clases con fechas invalidas, cupos fuera de capacidad o entrenador no asignado.
- `MembresiaBuilder` valida tipo, precio, estado y calcula vencimientos.
- `UsuarioBuilder` valida email, rol, estado y aplica `password_hash`.
- `RegistroProgresoBuilder` permite registrar WODs con atributos opcionales como tiempo, repeticiones o peso, pero impide registros completamente vacios.
- `ReporteBuilder` construye reportes complejos con filtros de fecha, tipo de consulta y variantes de salida sin duplicar logica entre visualizacion, CSV o PDF.

El beneficio tecnico es que las entidades llegan a la capa DAO en un estado consistente, reduciendo errores antes de persistir datos.

## 3. Gestion de la Base de Datos: SQLite y PDO

El acceso a datos esta encapsulado en clases DAO, una por modulo principal. Cada DAO utiliza **PDO** como capa de abstraccion para ejecutar consultas SQL.

Para el prototipo se utiliza **SQLite local** en:

```text
3.Codigo/data/ironclad_box.sqlite
```

Esto facilita la ejecucion en cualquier equipo sin instalar un servidor MySQL o PostgreSQL. Es una decision practica para pruebas academicas y demostraciones rapidas.

La base objetivo del sistema es **MySQL relacional**. Gracias a **PDO**, la migracion requiere principalmente cambiar la cadena de conexion en los DAO:

```php
$dsn = 'mysql:host=localhost;dbname=ironclad_box;charset=utf8mb4';
```

La estructura del codigo no cambia: controladores, servicios, builders y entidades siguen funcionando igual. Esta es una ventaja directa de usar una capa DAO desacoplada.

## 4. Guia de Flujos Transaccionales

Esta seccion sirve como guion de demostracion para defender el sistema ante profesores o clientes.

### Demostracion del Administrador

1. Abrir la gestion de clases:

```text
http://127.0.0.1:8000/views/gestion_clases.html
```

2. Crear una clase indicando dia, hora, duracion, cupo maximo y entrenador.
3. Explicar que la peticion llega a `ClaseController.php`.
4. El controlador delega en `ClaseService.php`.
5. `ClaseService` utiliza `ClaseBuilder` para construir el objeto con validaciones.
6. Luego valida reglas criticas:
   - disponibilidad del entrenador,
   - solapamiento de horarios,
   - limite de cupos.
7. Finalmente `ClaseDAO` persiste la clase.

Para demostrar validacion, intentar crear otra clase en un horario solapado. El sistema debe rechazarla.

Luego abrir gestion de membresias:

```text
http://127.0.0.1:8000/views/gestion_membresias.html
```

1. Asignar una membresia a un atleta.
2. Registrar pago.
3. Explicar que `MembresiaService` reutiliza `MembresiaBuilder`.
4. Al pagar, el sistema cambia el estado a `Pagado` y calcula automaticamente una nueva fecha de vencimiento a 30 dias.

Luego abrir reportes administrativos:

```text
http://127.0.0.1:8000/views/reportes_admin.html
```

1. Seleccionar el tipo de reporte: `Finanzas` o `Asistencia`.
2. Seleccionar fecha inicio y fecha fin.
3. Generar el reporte para visualizar resultados preliminares.
4. Explicar que `ReporteController.php` delega en `ReporteService.php`.
5. `ReporteService` consulta datos consolidados en `ReporteDAO.php` y construye la salida mediante `ReporteBuilder`.
6. Exportar resultados con `Exportar CSV`.
7. Usar `Exportar PDF` para imprimir o guardar la vista desde el navegador.

### Demostracion del Entrenador

Abrir:

```text
http://127.0.0.1:8000/views/seguimiento_progreso.html
```

1. Seleccionar un atleta.
2. Registrar un resultado WOD.
3. Llenar solo algunos campos, por ejemplo repeticiones y peso, dejando tiempo vacio.
4. Explicar que `RegistroProgresoBuilder` acepta atributos opcionales porque distintos WODs se miden de forma diferente.
5. Intentar registrar un resultado sin tiempo, sin repeticiones y sin peso. El sistema debe impedirlo.
6. Mostrar el historial inferior del atleta.

Este flujo demuestra que el **Builder** permite flexibilidad sin perder coherencia de datos.

### Demostracion del Atleta: Validacion Cruzada

Abrir:

```text
http://127.0.0.1:8000/views/reservas_atleta.html
```

1. Seleccionar un atleta.
2. Revisar clases disponibles.
3. Hacer clic en `Reservar`.
4. Explicar que este es el flujo transaccional mas importante del MVP.

Al reservar, `ReservaService` coordina validaciones cruzadas entre modulos:

- consulta en `MembresiaDAO` que el atleta tenga membresia `Pagado` y no vencida,
- consulta en `ClaseDAO`/tablas de clases que existan cupos disponibles,
- valida que no haya una reserva activa duplicada,
- crea la reserva en `ReservaDAO`,
- descuenta 1 cupo disponible de la clase.

Luego cancelar la reserva:

- el estado pasa a `Cancelada`,
- el cupo se libera sumando 1 nuevamente.

Este flujo demuestra **validacion cruzada**, **consistencia transaccional** y separacion de responsabilidades.

### Demostracion Visual REQ004-3

Abrir la vista del atleta:

```text
http://127.0.0.1:8000/views/progreso_atleta.html
```

O la vista del entrenador:

```text
http://127.0.0.1:8000/views/seguimiento_progreso.html
```

1. Seleccionar un atleta.
2. Registrar varios resultados de progreso.
3. Mostrar que el grafico de evolucion se actualiza dinamicamente.
4. Explicar que el grafico usa **Chart.js** por CDN.
5. El frontend consume el endpoint:

```text
ProgresoController.php?action=obtenerDatosGrafico&idAtleta=1
```

Este endpoint devuelve JSON con:

- `fechas`,
- `puntuaciones`,
- `pesos`.

Chart.js solo se usa como componente visual. La arquitectura backend se mantiene igual: el controller consulta el historial mediante `ProgresoService` y `ProgresoDAO`.

### Demostracion de Comunicacion REQ005

#### Entrenador

Abrir:

```text
http://127.0.0.1:8000/views/comunicacion_entrenador.html
```

1. Seleccionar un entrenador remitente.
2. Elegir el tipo de comunicacion:
   - `Mensaje individual` para un atleta especifico.
   - `Anuncio general` para todos los atletas.
3. Redactar el contenido.
4. Enviar el mensaje.
5. Explicar que la peticion llega a `ComunicacionController.php`, que delega en `ComunicacionService.php`.
6. `ComunicacionService` valida entrenador, destinatario y contenido.
7. `ComunicacionDAO` persiste el mensaje en la tabla `mensajes`.
8. Mostrar el historial enviado en la misma pantalla.

#### Atleta

Abrir:

```text
http://127.0.0.1:8000/views/bandeja_atleta.html
```

1. Seleccionar el atleta destinatario.
2. Visualizar su bandeja de entrada.
3. Confirmar que aparecen:
   - mensajes directos enviados a ese atleta,
   - anuncios generales enviados a todos.
4. Explicar que esta pantalla consume `ComunicacionController.php?action=recibidos&idAtleta=...`.

Este flujo demuestra la conexion entre actores: el entrenador emite comunicacion desde su panel y el atleta la recibe desde su bandeja.

## 5. Instrucciones de Ejecucion

### Requisitos

- PHP instalado y disponible en consola.
- Navegador web moderno.
- No se requiere instalar MySQL para el prototipo.

### Levantar servidor local

Desde la carpeta `3.Codigo` ejecutar:

```bash
php -S 127.0.0.1:8000 -t .
```

### URLs principales

Administrador:

```text
http://127.0.0.1:8000/views/gestion_clases.html
http://127.0.0.1:8000/views/gestion_membresias.html
http://127.0.0.1:8000/views/gestion_usuarios.html
http://127.0.0.1:8000/views/reportes_admin.html
```

Entrenador:

```text
http://127.0.0.1:8000/views/seguimiento_progreso.html
```

Atleta:

```text
http://127.0.0.1:8000/views/reservas_atleta.html
http://127.0.0.1:8000/views/progreso_atleta.html
http://127.0.0.1:8000/views/membresia_atleta.html
http://127.0.0.1:8000/views/bandeja_atleta.html
```

Comunicacion:

```text
http://127.0.0.1:8000/views/comunicacion_entrenador.html
```

### Validacion rapida

1. Crear clase como administrador.
2. Asignar y pagar membresia.
3. Reservar clase como atleta.
4. Registrar progreso como entrenador o atleta.
5. Revisar grafico de evolucion.
6. Enviar un mensaje como entrenador y leerlo desde la bandeja del atleta.
7. Generar un reporte administrativo y exportarlo a CSV/PDF.

## Nota de Cierre

El **Release 1** del MVP queda funcionalmente completo. El sistema cubre los casos de uso principales de **Administrador**, **Entrenador** y **Atleta**, manteniendo una arquitectura clara con **MVC**, **Builder**, **DAO** y **PDO**.
