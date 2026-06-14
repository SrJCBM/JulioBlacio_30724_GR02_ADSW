# IronClad Box - Core Backend

Documento de trazabilidad tecnica del proyecto **IronClad Box**, una plataforma web para la gestion operativa de un gimnasio de CrossFit. Este archivo resume el estado actual del core implementado, los modulos completados, sus archivos fuente y las reglas de negocio cubiertas hasta este punto del sprint.

## Arquitectura y Stack Tecnologico

| Area | Decision tecnica |
| --- | --- |
| Frontend | HTML5, CSS3 y JavaScript puro (Vanilla JS), sin frameworks |
| Backend | PHP nativo |
| Acceso a datos | PDO |
| Base local simulada | SQLite en `3.Codigo/data/ironclad_box.sqlite` |
| Base objetivo | MySQL relacional, documentada en comentarios dentro de los DAO |
| Arquitectura | Arquitectura por capas con patron MVC |
| Patron creacional | Builder aplicado en la capa de logica de negocio para construir entidades con validaciones progresivas |

### Separacion por capas

- **Presentacion (View):** archivos HTML y JavaScript en `3.Codigo/views` y `3.Codigo/assets/js`.
- **Controladores (Controller):** endpoints PHP en `3.Codigo/controllers`.
- **Logica de negocio (Service / Builder):** servicios en `3.Codigo/services` y builders en `3.Codigo/builders`.
- **Acceso a datos (DAO):** repositorios PDO en `3.Codigo/dao`.
- **Entidades:** modelos de dominio en `3.Codigo/models`.

## Modulos Completados

### [x] Modulo 1: Gestion de la Agenda de Clases e Inscripciones (CUC-ADM-01 / CUC-ATL-01)

Conforme al Diagrama de Arquitectura de Capas, la logica de reservas **no constituye un modulo independiente**: pertenece a la gestion de Clases. Por ello `ClaseController` y `ClaseService` absorben tambien las inscripciones de atletas (validacion cruzada de membresia y descuento transaccional de cupos), eliminando los antiguos `ReservaController`, `ReservaService` y `ReservaDAO`.

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Clase.php` |
| Entidad de inscripcion | `3.Codigo/models/Reserva.php` |
| Builder | `3.Codigo/builders/ClaseBuilder.php` |
| Service | `3.Codigo/services/ClaseService.php` |
| DAO | `3.Codigo/dao/ClaseDAO.php` |
| DAO cruzado (validacion de membresia) | `3.Codigo/dao/MembresiaDAO.php` |
| Controller | `3.Codigo/controllers/ClaseController.php` |
| HTML (administracion) | `3.Codigo/views/gestion_clases.html` |
| JS (administracion) | `3.Codigo/assets/js/app.js` |
| HTML (inscripciones del atleta) | `3.Codigo/views/reservas_atleta.html` |
| JS (inscripciones del atleta) | `3.Codigo/assets/js/reservas.js` |

Reglas principales implementadas:

- Creacion, edicion, eliminacion y listado de clases.
- Validacion de campos obligatorios: dia, hora, duracion, cupo maximo y entrenador.
- Validacion de disponibilidad del entrenador.
- Prevencion de solapamiento de horarios.
- Control de cupo maximo contra la capacidad del box.
- Construccion controlada de la entidad mediante `ClaseBuilder`.
- Inscripcion de atletas con validacion cruzada de membresia (`ClaseService` consulta `MembresiaDAO` exigiendo estado `Pagado` y vigencia).
- Bloqueo de reservas duplicadas activas para el mismo atleta y la misma clase.
- Descuento transaccional de 1 cupo al confirmar y liberacion de 1 cupo al cancelar, mediante `ClaseDAO::reservarCupo()` y `ClaseDAO::cancelarReservaYLiberarCupo()` (uso de `beginTransaction`).

Patron Builder y documento GR2_30724: `ClaseBuilder` es el responsable de cumplir **REQ017 (control de cupos)** validando que el cupo maximo no exceda la capacidad del box, y **REQ018 (validacion de horarios)** garantizando dia/hora/duracion consistentes antes de delegar las reglas de agenda a `ClaseService`.

Mapeo V4 de requerimientos atomicos cubiertos:

- **REQ013: Creacion de clases.** Cumple las tareas 1, 2 y 3 mediante el formulario de `gestion_clases.html`, las validaciones progresivas de `ClaseBuilder` y la persistencia coordinada por `ClaseService` y `ClaseDAO`. **Decision de diseno:** en un box de CrossFit la sesion se identifica de forma univoca por `dia + hora + entrenador` (no por un nombre arbitrario); por ello la entidad `Clase` no incorpora un campo "nombre" libre, evitando sobreingenieria y datos redundantes. El elemento "Nombre de la clase" del REQ013 queda cubierto conceptualmente por esta identidad compuesta.
- **REQ014: Edicion de clases.** Cumple las tareas 1, 2 y 3 mediante la interfaz de edicion, los metodos de actualizacion en `ClaseController` y la persistencia en `ClaseDAO`.
- **REQ015: Eliminacion de clases.** Cumple las tareas 1, 2 y 3 mediante la accion de eliminacion desde la vista, la coordinacion del controlador/servicio y la eliminacion logica o fisica desde el DAO segun la operacion configurada.
- **REQ016: Asignacion de entrenadores.** Cumple las tareas 1, 2 y 3 al exigir el `idEntrenador` durante la creacion/edicion de la clase, validar su presencia en `ClaseBuilder` y persistir la relacion con la clase.
- **REQ017: Control de cupos.** Cumple las tareas 1, 2 y 3 mediante la validacion de capacidad maxima en `ClaseBuilder`, el control de `cuposDisponibles` en la entidad y la resta/liberacion dinamica de cupos desde el modulo de reservas. La `Agenda programada` muestra explicitamente los tres valores que pide el requerimiento: **cupos ocupados** (`cupoMaximo - cuposDisponibles`), **disponibles** y **capacidad maxima**.
- **REQ018: Validacion de horarios.** Cumple las tareas 1, 2 y 3 mediante las reglas internas de `ClaseService` que previenen solapamientos, validan disponibilidad del entrenador y bloquean la persistencia de horarios inconsistentes.

### [x] Modulo 2: Gestion de Membresias y Pagos (CUC-ADM-02)

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Membresia.php` |
| Builder | `3.Codigo/builders/MembresiaBuilder.php` |
| Service | `3.Codigo/services/MembresiaService.php` |
| DAO | `3.Codigo/dao/MembresiaDAO.php` |
| Controller | `3.Codigo/controllers/MembresiaController.php` |
| HTML | `3.Codigo/views/gestion_membresias.html` |
| JS | `3.Codigo/assets/js/membresias.js` |

Reglas principales implementadas:

- Creacion, asignacion y edicion de membresias a atletas (formulario reutilizado en modo crear/editar).
- Estados validos: `Pagado`, `Pendiente`, `Vencido`.
- Registro de pago con cambio automatico a estado `Pagado`.
- Calculo automatico de fecha de vencimiento a 30 dias.
- Listado de atletas con estado actual de membresia y expiracion.
- Marcado automatico de membresias vencidas al consultar.
- Construccion controlada de la entidad mediante `MembresiaBuilder`.

Mapeo V4 de requerimientos atomicos cubiertos:

- **REQ007: Creacion de membresias.** Cumple las tareas 1, 2 y 3: formulario rotulado **"Crear / Asignar membresia"** en `gestion_membresias.html` que deja explicita la creacion del registro, definicion de beneficios/tipo/precio mediante `MembresiaBuilder`, y almacenamiento en BD mediante `MembresiaDAO::crear`.
- **REQ008: Edicion de membresias.** Cumple las tareas 1, 2 y 3: boton **"Editar"** por fila que carga la membresia en el formulario (modo edicion con "Cancelar edicion"), reconstruccion validada de la entidad en `MembresiaService::actualizar` mediante `MembresiaBuilder`, y persistencia con sentencia preparada en `MembresiaDAO::actualizar` (la accion `editar` de `MembresiaController` la expone). `actualizarTrasPago` reutiliza este mismo metodo.
- **REQ009: Asignacion de membresias.** Cumple las tareas 1, 2 y 3: interfaz de asignacion a atletas, relacion con atletas mediante `idAtleta`, y validacion de existencia/vigencia desde `MembresiaService`.
- **REQ010: Registro de pagos.** Cumple las tareas 1, 2 y 3: formulario/accion de pago, historial transaccional representado por la membresia actualizada, y actualizacion de estado a `Pagado`.
- **REQ011: Consulta de membresias.** Cumple las tareas 1, 2 y 3: pantalla de consulta administrativa y personal, visualizacion de vigencia/estado, y validacion de acceso por `idAtleta`.
- **REQ012: Control de vencimientos.** Cumple las tareas 1, 2 y 3: calculo automatico de fechas a 30 dias mediante `MembresiaBuilder`, alertas visuales por estado `Pagado`/`Pendiente`/`Vencido`, y bloqueo de beneficios en modulos cruzados como reservas si la membresia no esta vigente.

### [x] Modulo 3: Gestion de Usuarios (CUC-ADM-03)

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Usuario.php` |
| Builder | `3.Codigo/builders/UsuarioBuilder.php` |
| Service | `3.Codigo/services/UsuarioService.php` |
| DAO | `3.Codigo/dao/UsuarioDAO.php` |
| Controller | `3.Codigo/controllers/UsuarioController.php` |
| HTML | `3.Codigo/views/gestion_usuarios.html` |
| JS | `3.Codigo/assets/js/usuarios.js` |

Reglas principales implementadas:

- Creacion, edicion y listado de usuarios.
- Roles validos: `Administrador`, `Entrenador`, `Atleta`.
- Estados validos: `Activo`, `Inactivo`.
- Validacion de formato de email.
- Bloqueo de emails duplicados.
- Encriptacion de contrasenas con `password_hash`.
- Baja logica mediante cambio de estado a `Inactivo`.
- Conservacion del hash existente cuando se edita un usuario sin cambiar contrasena.
- Construccion controlada de la entidad mediante `UsuarioBuilder`.

Mapeo V4 de requerimientos atomicos cubiertos:

- **REQ001: Creacion de cuentas.** Cumple las tareas 1, 2 y 3: diseno de interfaz en `gestion_usuarios.html`, validaciones de formulario y reglas en `UsuarioBuilder`, y almacenamiento en BD mediante `UsuarioDAO`.
- **REQ002: Edicion de cuentas.** Cumple las tareas 1, 2 y 3: interfaz de edicion reutilizando el formulario, actualizacion mediante `UsuarioService::editar`, y persistencia en `UsuarioDAO::actualizar`.
- **REQ003: Desactivacion de cuentas.** Cumple las tareas 1, 2 y 3: campo `estado` en la entidad, desactivacion logica cambiando a `Inactivo`, y bloqueo operativo al separar usuarios activos/inactivos sin eliminar registros.
- **REQ004: Creacion de roles.** **Decision de diseno (roles fijos):** el dominio del box opera con un catalogo cerrado de tres roles (`Administrador`, `Entrenador`, `Atleta`) validado en `UsuarioBuilder`. No se expone una pantalla de "crear rol" libre de forma deliberada, para evitar sobreingenieria y roles invalidos; el catalogo se gestiona como constante de negocio.
- **REQ005: Asignacion de roles.** Cumple las tareas 1, 2 y 3: seleccion de rol desde el `<select>` del formulario de usuario, relacion usuario-rol persistida en la tabla `usuarios`, y validacion del rol en la capa de servicio/Builder.
- **REQ006: Asignacion de permisos.** **Decision de diseno (permisos por rol):** los permisos no se administran individualmente; se derivan de forma implicita del rol mediante la separacion de controladores, vistas y flujos por actor. Una matriz de permisos granular queda fuera del alcance del MVP para evitar sobreingenieria.

### [x] Modulo 4: Seguimiento del Progreso Deportivo - Entrenador (CUC-ENT-01)

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/RegistroProgreso.php` |
| Builder | `3.Codigo/builders/RegistroProgresoBuilder.php` |
| Service | `3.Codigo/services/ProgresoService.php` |
| DAO | `3.Codigo/dao/ProgresoDAO.php` |
| Controller | `3.Codigo/controllers/ProgresoController.php` |
| HTML | `3.Codigo/views/seguimiento_progreso.html` |
| JS | `3.Codigo/assets/js/progreso.js` |

Reglas principales implementadas:

- Registro de resultados WOD por atleta.
- Campos obligatorios: fecha e id del atleta.
- Campos opcionales segun tipo de WOD: tiempo, repeticiones, peso, puntuacion y notas.
- Validacion de coherencia para impedir registros completamente vacios.
- Calculo de puntuacion base cuando no se ingresa puntuacion manual.
- Consulta de historial por atleta ordenado por fecha descendente.
- Construccion controlada de la entidad mediante `RegistroProgresoBuilder`.

### [x] Modulo 5: Gestion de Reservas de Clases - Atleta (CUC-ATL-01)

Este módulo no existe como entidad independiente; su lógica y validaciones cruzadas han sido absorbidas por el Módulo 1 (Gestión de la Agenda de Clases), cumpliendo con nuestra Arquitectura de Capas.

### [x] Modulo 6: Registro de Progreso Personal - Atleta (CUC-ATL-02)

| Tipo | Archivo |
| --- | --- |
| Entidad reutilizada | `3.Codigo/models/RegistroProgreso.php` |
| Builder reutilizado | `3.Codigo/builders/RegistroProgresoBuilder.php` |
| Service reutilizado | `3.Codigo/services/ProgresoService.php` |
| DAO reutilizado | `3.Codigo/dao/ProgresoDAO.php` |
| Controller actualizado | `3.Codigo/controllers/ProgresoController.php` |
| HTML | `3.Codigo/views/progreso_atleta.html` |
| JS | `3.Codigo/assets/js/progreso_atleta.js` |

Reglas principales implementadas:

- Reutilizar la entidad, builder, service y DAO del modulo de progreso del entrenador.
- Guardar resultados WOD asociados al `id_atleta` por parametro o sesion.
- Permitir campos opcionales: tiempo, repeticiones, peso y notas personales.
- Impedir registros completamente vacios.
- Consultar el historial exclusivo del atleta ordenado por fecha.
- Mantener placeholder de futura integracion con apps de salud.

### [x] Modulo 7: Gestion de Membresia Personal - Atleta (CUC-ATL-03)

| Tipo | Archivo |
| --- | --- |
| Entidad reutilizada | `3.Codigo/models/Membresia.php` |
| Builder reutilizado | `3.Codigo/builders/MembresiaBuilder.php` |
| Service reutilizado | `3.Codigo/services/MembresiaService.php` |
| DAO reutilizado | `3.Codigo/dao/MembresiaDAO.php` |
| Controller actualizado | `3.Codigo/controllers/MembresiaController.php` |
| HTML | `3.Codigo/views/membresia_atleta.html` |
| JS | `3.Codigo/assets/js/membresia_atleta.js` |

Reglas principales implementadas:

- Consultar la membresia actual del atleta por parametro o sesion.
- Mostrar tipo, precio, fecha de vencimiento y estado financiero.
- Simular pago en linea con formulario de metodo de pago y tarjeta.
- Reutilizar `MembresiaService::registrarPago` para renovar membresia.
- Cambiar estado a `Pagado` tras confirmar el pago.
- Extender fecha de vencimiento a 30 dias desde la fecha de pago.

Mapeo V4 de requerimientos atomicos cubiertos:

- **REQ007: Creacion de membresias.** Cumple las tareas 1, 2 y 3 reutilizando el formulario/flujo financiero, beneficios tipo/precio en `MembresiaBuilder`, y almacenamiento desde `MembresiaDAO`.
- **REQ008: Edicion de membresias.** Cumple las tareas 1, 2 y 3 mediante la actualizacion de membresia durante renovaciones, actualizacion de precio/estado y persistencia con `actualizarTrasPago`.
- **REQ009: Asignacion de membresias.** Cumple las tareas 1, 2 y 3 relacionando membresia-atleta por `idAtleta`, validando existencia de atleta y vigencia de la membresia.
- **REQ010: Registro de pagos.** Cumple las tareas 1, 2 y 3 desde la vista `membresia_atleta.html`, simulando pago, registrando la transaccion logica y actualizando estado a `Pagado`.
- **REQ011: Consulta de membresias.** Cumple las tareas 1, 2 y 3 con la accion `miMembresia`, visualizacion de vigencia/estado y validacion de acceso por atleta.
- **REQ012: Control de vencimientos.** Cumple las tareas 1, 2 y 3 extendiendo automaticamente la fecha a 30 dias con `MembresiaBuilder`, mostrando estados/alertas y bloqueando beneficios como reservas si la membresia esta vencida o pendiente.

### [x] Implementación de componentes visuales - Gráficos de Progreso (Pendiente de Backlog V5)

Objetivo del requerimiento:

Mostrar la evolucion deportiva de los atletas mediante graficos interactivos para las vistas del Atleta y del Entrenador.

| Tipo | Archivo |
| --- | --- |
| Controller actualizado | `3.Codigo/controllers/ProgresoController.php` |
| Vista Atleta actualizada | `3.Codigo/views/progreso_atleta.html` |
| JS Atleta actualizado | `3.Codigo/assets/js/progreso_atleta.js` |
| Vista Entrenador actualizada | `3.Codigo/views/seguimiento_progreso.html` |
| JS Entrenador actualizado | `3.Codigo/assets/js/progreso.js` |
| Libreria visual | Chart.js via CDN |

Reglas principales implementadas:

- Reutilizar el historial de `ProgresoService` y `ProgresoDAO`.
- Exponer endpoint `obtenerDatosGrafico` con arreglos `fechas`, `puntuaciones` y `pesos`.
- Renderizar grafico de lineas con evolucion de puntuacion y peso.
- Actualizar dinamicamente el grafico al seleccionar un atleta.
- Mantener PHP nativo y Vanilla JS, incorporando Chart.js solo como libreria visual por CDN.

## Trabajo en Progreso (WIP) - Release 1

### Fase de Pruebas Integrales y Preparacion para Despliegue

No quedan modulos funcionales pendientes para el Release 1. La siguiente fase corresponde a pruebas integrales, estabilizacion, documentacion operativa y preparacion para despliegue.

## Estado General del Sprint

| Modulo | Estado | Actor principal |
| --- | --- | --- |
| CUC-ADM-01 Agenda de Clases e Inscripciones | Completado | Administrador / Atleta |
| CUC-ADM-02 Membresias y Pagos | Completado | Administrador |
| CUC-ADM-03 Gestion de Usuarios | Completado | Administrador |
| CUC-ENT-01 Seguimiento Deportivo | Completado | Entrenador |
| CUC-ATL-01 Reservas de Clases (consolidado en CUC-ADM-01) | Completado | Atleta |
| CUC-ATL-02 Progreso Personal | Completado | Atleta |
| CUC-ATL-03 Membresia Personal | Completado | Atleta |
| Gráficos de Progreso | Completado | Atleta / Entrenador |

## Cierre de Release 1

Con la finalizacion de los Graficos de Progreso se da por concluido el desarrollo del **Release 1** del MVP de IronClad Box. Este release cubre exitosamente los casos de uso principales de los tres actores del sistema: **Administrador**, **Entrenador** y **Atleta**.

## Release 2

El Release 2 inicia la ampliacion del sistema hacia capacidades colaborativas, incorporando comunicacion interna entre entrenadores y atletas sin alterar la arquitectura base del MVP.

## Modulos Completados - Release 2

### [x] Comunicación entre entrenadores y atletas (Pendiente de asignación en Backlog V5)

Objetivo del requerimiento:

Permitir que los entrenadores envien mensajes directos o anuncios generales, y que los atletas consulten una bandeja de entrada con los mensajes recibidos.

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Mensaje.php` |
| Service | `3.Codigo/services/ComunicacionService.php` |
| DAO | `3.Codigo/dao/ComunicacionDAO.php` |
| Controller | `3.Codigo/controllers/ComunicacionController.php` |
| HTML Entrenador | `3.Codigo/views/comunicacion_entrenador.html` |
| JS Entrenador | `3.Codigo/assets/js/comunicacion_entrenador.js` |
| HTML Atleta | `3.Codigo/views/bandeja_atleta.html` |
| JS Atleta | `3.Codigo/assets/js/bandeja_atleta.js` |

Reglas principales implementadas:

- Enviar mensajes individuales a un atleta especifico.
- Enviar anuncios generales a todos los atletas.
- Validar entrenador remitente y atleta destinatario.
- Persistir mensajes en la tabla `mensajes`.
- Consultar bandeja de entrada por atleta, incluyendo mensajes directos y anuncios.
- Consultar historial de mensajes enviados por entrenadores.

### [x] Generación de reportes administrativos (Pendiente de asignación en Backlog V5)

Objetivo del requerimiento:

Permitir que el administrador genere reportes filtrados por fechas y tipo, visualice resultados preliminares y exporte datos administrativos.

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Reporte.php` |
| Builder | `3.Codigo/builders/ReporteBuilder.php` |
| Service | `3.Codigo/services/ReporteService.php` |
| DAO | `3.Codigo/dao/ReporteDAO.php` |
| Controller | `3.Codigo/controllers/ReporteController.php` |
| HTML Administrador | `3.Codigo/views/reportes_admin.html` |
| JS Administrador | `3.Codigo/assets/js/reportes_admin.js` |

Reglas principales implementadas:

- Generar reportes de `Finanzas` y `Asistencia`.
- Aplicar filtros por fecha inicio y fecha fin.
- Consolidar datos mediante consultas JOIN en el DAO (`ReporteDAO::consultarFinanzas` y `ReporteDAO::consultarAsistencia`).
- Construir el resultado final con `ReporteBuilder` (`formatearDatos` para columnas y resumen segun tipo).
- Devolver JSON para visualizacion en pantalla.
- Exportar CSV nativo mediante `fputcsv` y cabeceras HTTP.
- Preparar salida PDF mediante impresion de pantalla del navegador.

## Trabajo en Progreso (WIP) - Release 2

No quedan funcionalidades pendientes en el alcance actual del Release 2. Los módulos de Comunicación y Reportes han sido completados y están a la espera de su asignación oficial de requerimiento en el Backlog V5

## Validaciones Tecnicas Realizadas

- Revision de sintaxis PHP con `php -l`.
- Revision de sintaxis JavaScript con `node --check`.
- Pruebas funcionales directas contra services usando SQLite en memoria.
- Smoke tests HTTP contra controllers usando el servidor integrado de PHP.
