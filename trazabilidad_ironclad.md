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

### [x] Modulo 1: Gestion de la Agenda de Clases (CUC-ADM-01)

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Clase.php` |
| Builder | `3.Codigo/builders/ClaseBuilder.php` |
| Service | `3.Codigo/services/ClaseService.php` |
| DAO | `3.Codigo/dao/ClaseDAO.php` |
| Controller | `3.Codigo/controllers/ClaseController.php` |
| HTML | `3.Codigo/views/gestion_clases.html` |
| JS | `3.Codigo/assets/js/app.js` |

Reglas principales implementadas:

- Creacion, edicion, eliminacion y listado de clases.
- Validacion de campos obligatorios: dia, hora, duracion, cupo maximo y entrenador.
- Validacion de disponibilidad del entrenador.
- Prevencion de solapamiento de horarios.
- Control de cupo maximo contra la capacidad del box.
- Construccion controlada de la entidad mediante `ClaseBuilder`.

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

- Asignacion de membresias a atletas.
- Estados validos: `Pagado`, `Pendiente`, `Vencido`.
- Registro de pago con cambio automatico a estado `Pagado`.
- Calculo automatico de fecha de vencimiento a 30 dias.
- Listado de atletas con estado actual de membresia y expiracion.
- Marcado automatico de membresias vencidas al consultar.
- Construccion controlada de la entidad mediante `MembresiaBuilder`.

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

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Reserva.php` |
| Service | `3.Codigo/services/ReservaService.php` |
| DAO | `3.Codigo/dao/ReservaDAO.php` |
| Controller | `3.Codigo/controllers/ReservaController.php` |
| HTML | `3.Codigo/views/reservas_atleta.html` |
| JS | `3.Codigo/assets/js/reservas.js` |

Reglas principales implementadas:

- Consulta de clases disponibles para el atleta.
- Consulta de reservas activas del atleta.
- Validacion de membresia vigente antes de reservar.
- La membresia debe estar en estado `Pagado` y no vencida.
- Consulta de cupos disponibles de la clase seleccionada.
- Bloqueo de reserva si `cuposDisponibles <= 0`.
- Descuento de 1 cupo disponible al confirmar una reserva.
- Cancelacion logica de la reserva mediante estado `Cancelada`.
- Liberacion de 1 cupo disponible al cancelar una reserva activa.
- Prevencion de reservas duplicadas activas para el mismo atleta y la misma clase.

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

### [x] REQ004-3: Implementacion de componentes visuales - Graficos de Progreso

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
| CUC-ADM-01 Agenda de Clases | Completado | Administrador |
| CUC-ADM-02 Membresias y Pagos | Completado | Administrador |
| CUC-ADM-03 Gestion de Usuarios | Completado | Administrador |
| CUC-ENT-01 Seguimiento Deportivo | Completado | Entrenador |
| CUC-ATL-01 Reservas de Clases | Completado | Atleta |
| CUC-ATL-02 Progreso Personal | Completado | Atleta |
| CUC-ATL-03 Membresia Personal | Completado | Atleta |
| REQ004-3 Graficos de Progreso | Completado | Atleta / Entrenador |

## Cierre de Release 1

Con la finalizacion de REQ004-3 se da por concluido el desarrollo del **Release 1** del MVP de IronClad Box. Este release cubre exitosamente los casos de uso principales de los tres actores del sistema: **Administrador**, **Entrenador** y **Atleta**.

## Release 2

El Release 2 inicia la ampliacion del sistema hacia capacidades colaborativas, incorporando comunicacion interna entre entrenadores y atletas sin alterar la arquitectura base del MVP.

## Modulos Completados - Release 2

### [x] REQ005: Comunicacion entre entrenadores y atletas (RQ-05)

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

## Trabajo en Progreso (WIP) - Release 2

### [ ] REQ006: Generacion de reportes administrativos (Uso de ReporteBuilder)

Objetivo del requerimiento:

Permitir que el administrador genere reportes filtrados por fechas y tipo, visualice resultados preliminares y exporte datos administrativos.

Componentes en construccion:

| Tipo | Archivo |
| --- | --- |
| Entidad | `3.Codigo/models/Reporte.php` |
| Builder | `3.Codigo/builders/ReporteBuilder.php` |
| Service | `3.Codigo/services/ReporteService.php` |
| DAO | `3.Codigo/dao/ReporteDAO.php` |
| Controller | `3.Codigo/controllers/ReporteController.php` |
| HTML Administrador | `3.Codigo/views/reportes_admin.html` |
| JS Administrador | `3.Codigo/assets/js/reportes_admin.js` |

Reglas de negocio objetivo:

- Generar reportes de `Finanzas` y `Asistencia`.
- Aplicar filtros por fecha inicio y fecha fin.
- Consolidar datos mediante consultas JOIN en el DAO.
- Construir el resultado final con `ReporteBuilder`.
- Devolver JSON para visualizacion en pantalla.
- Exportar CSV nativo mediante cabeceras HTTP.
- Preparar salida PDF mediante impresion de pantalla del navegador.

## Validaciones Tecnicas Realizadas

- Revision de sintaxis PHP con `php -l`.
- Revision de sintaxis JavaScript con `node --check`.
- Pruebas funcionales directas contra services usando SQLite en memoria.
- Smoke tests HTTP contra controllers usando el servidor integrado de PHP.
