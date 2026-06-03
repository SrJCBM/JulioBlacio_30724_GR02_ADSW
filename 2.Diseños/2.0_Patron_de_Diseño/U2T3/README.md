# U2T3 - Observer y Strategy en el CRUD del estudiante

## Proposito

Aplicar los patrones de comportamiento Observer y Strategy sobre el CRUD del
estudiante actualizado en U2T2. Observer gestiona eventos del sistema al crear,
actualizar o eliminar estudiantes. Strategy permite cambiar dinamicamente el
algoritmo de busqueda sin modificar el cliente principal.

## Insumos obligatorios

| Insumo | Implementacion |
| --- | --- |
| Proyecto del CRUD actualizado con patrones de U2T2 | Se conserva Adapter para entrada externa y Decorator para validacion/auditoria del CRUD. |
| Escenario de notificacion | Al registrar, actualizar o eliminar un estudiante, `ControlEstudiante` notifica a observadores de auditoria y notificacion. |
| Escenario de comportamiento variable | Busqueda dinamica por ID o por Nombre mediante estrategias intercambiables. |
| Evidencias de requisitos asociados a flexibilidad y mantenibilidad | Prueba `PruebaObserverStrategy`, matriz de trazabilidad y justificacion tecnica. |

## Observer

Evento relevante del CRUD: estudiante registrado, actualizado o eliminado.

Clases involucradas:

- `SujetoEventosEstudiante`: contrato para registrar, quitar y notificar observadores.
- `ControlEstudiante`: sujeto concreto que emite eventos del CRUD.
- `EventoEstudiante`: contiene tipo de evento, estudiante y mensaje.
- `TipoEventoEstudiante`: `REGISTRADO`, `ACTUALIZADO`, `ELIMINADO`.
- `ObservadorEstudiante`: contrato comun de los observadores.
- `ObservadorAuditoriaEstudiante`: registra eventos en una bitacora.
- `ObservadorNotificacionEstudiante`: genera la ultima notificacion del sistema.

## Strategy

Comportamiento variable: busqueda de estudiantes.

Clases involucradas:

- `EstrategiaBusquedaEstudiante`: interfaz comun para las estrategias.
- `BusquedaPorIdStrategy`: busca estudiantes por ID.
- `BusquedaPorNombreStrategy`: busca estudiantes por coincidencia parcial de nombre.
- `ControlEstudiante`: mantiene la estrategia actual y permite cambiarla con `cambiarEstrategiaBusqueda`.
- `CRUDEstudiantesGUI`: permite seleccionar busqueda por ID o Nombre.

## Diagrama PlantUML

El diagrama actualizado esta en:

```text
diagrama_observer_strategy.puml
```

## Matriz de trazabilidad

| Codigo | Requisito / necesidad | Evento o estrategia | Componentes | Evidencia |
| --- | --- | --- | --- | --- |
| RF-01 | Registrar estudiante desde entrada externa. | Evento `REGISTRADO` | `ControlEstudiante.agregarDesdeEntradaExterna`, `AdaptadorEstudianteExterno`, `ServicioCrudEstudiante`, `ObservadorAuditoriaEstudiante`, `ObservadorNotificacionEstudiante` | `PruebaObserverStrategy`: bloque Observer |
| RF-02 | Actualizar estudiante existente. | Evento `ACTUALIZADO` | `ControlEstudiante.actualizarEstudiante`, `ValidacionCrudEstudianteDecorator`, observadores | `PruebaObserverStrategy`: notificacion y bitacora |
| RF-03 | Eliminar estudiante segun regla academica. | Evento `ELIMINADO` si la operacion es exitosa | `ControlEstudiante.eliminarEstudiante`, `ValidacionCrudEstudianteDecorator`, observadores | `PruebaObserverStrategy`: menor de edad no notifica eliminacion exitosa; mayor de edad si |
| RF-04 | Consultar estudiantes con comportamiento flexible. | Strategy por ID / Nombre | `EstrategiaBusquedaEstudiante`, `BusquedaPorIdStrategy`, `BusquedaPorNombreStrategy`, `ControlEstudiante.buscarEstudiantes` | `PruebaObserverStrategy`: bloque Strategy |
| RNF-01 | Mantener bajo acoplamiento y facilidad de extension. | Observer / Strategy | Nuevos observadores o estrategias se agregan sin modificar la GUI ni el CRUD base | Diagrama y clases concretas |

## Matriz de trazabilidad global integrada

| Requisito / entrada | U2T1 Arquitectura | U2T2 Patrones estructurales | U2T3 Patrones de comportamiento | Evidencia final | Control de coherencia |
| --- | --- | --- | --- | --- | --- |
| RF priorizados del CRUD | Se implementan operaciones base del estudiante: registrar, consultar, actualizar y eliminar. | Se adaptan entradas externas y se extienden funcionalidades del CRUD. | Se agregan eventos del CRUD y estrategias de busqueda. | Codigo, diagramas PlantUML y pruebas de consola. | Ningun patron debe aparecer sin requisito asociado. |
| RNF de mantenibilidad | Se separan responsabilidades por capas. | Se reducen cambios directos con Adapter y Decorator. | Se facilita el cambio de comportamiento con Observer y Strategy. | Justificacion tecnica en los README. | La solucion debe ser mas flexible, no mas compleja innecesariamente. |
| Arquitectura | Se definen Presentacion, Logica de negocio, Datos y Modelo. | Los patrones estructurales se insertan respetando las capas. | Los patrones de comportamiento se ubican principalmente en la logica de negocio. | Esquemas PlantUML actualizados. | La presentacion no debe asumir responsabilidades de datos o reglas complejas. |
| Diseno orientado a objetos | Se identifican clases, metodos y responsabilidades. | Se aplican patrones estructurales Adapter y Decorator. | Se aplican patrones de comportamiento Observer y Strategy. | Diagramas de clases/componentes. | Los diagramas deben coincidir con la implementacion. |
| Evidencias de funcionamiento | Se ejecuta el CRUD base. | Se demuestra adaptacion y extension. | Se demuestra notificacion y cambio de estrategia. | Capturas, pruebas o demos de consola. | Cada evidencia debe vincularse a una tarea y requisito. |

## Criterios de aceptacion

| Criterio | Cumplimiento |
| --- | --- |
| Observer evidencia notificaciones entre sujeto y observadores. | Cumplido: `ControlEstudiante` notifica a auditoria y notificacion en eventos exitosos. |
| Strategy permite cambiar comportamiento sin modificar el cliente principal. | Cumplido: se cambia entre `BusquedaPorIdStrategy` y `BusquedaPorNombreStrategy`. |
| La implementacion conserva separacion por capas. | Cumplido: presentacion usa `ControlEstudiante`, logica concentra patrones, datos mantiene persistencia y modelo mantiene entidad. |
| Las evidencias demuestran flexibilidad y mantenibilidad. | Cumplido: prueba de consola, matriz y diagrama PlantUML. |

## Como ejecutar

Desde PowerShell, ubicarse en la carpeta padre de `U2T3`:

```powershell
cd "c:\Users\jcbla\Desktop\ESPE\Sexto Semestre\AlexanderToapanta_30724_G_ADSW\2.Diseños\2.0_Patron_de_Diseño"
```

Compilar:

```powershell
javac (Get-ChildItem "U2T3" -Recurse -Filter *.java | Select-Object -ExpandProperty FullName)
```

Ejecutar prueba de consola:

```powershell
java U2T3.presentacion.PruebaObserverStrategy
```

Ejecutar interfaz grafica:

```powershell
java U2T3.presentacion.CRUDEstudiantesGUI
```

## Evidencia esperada de consola

```text
=== Observer: eventos del CRUD ===
Agregado exitosamente: Ana Torres [Decorator: auditoria CREATE]
NOTIFICACION: evento REGISTRADO para estudiante ID 1
Agregado exitosamente: Luis Perez [Decorator: auditoria CREATE]
NOTIFICACION: evento REGISTRADO para estudiante ID 2
Actualizado exitosamente: ID 1 [Decorator: auditoria UPDATE]
NOTIFICACION: evento ACTUALIZADO para estudiante ID 1
Error: Regla academica - no se puede eliminar a 'Luis Perez' porque es menor de 18 anios (Edad: 17).
Eliminado exitosamente: Ana Torres Actualizada (ID 1) [Decorator: auditoria DELETE]
NOTIFICACION: evento ELIMINADO para estudiante ID 1

=== Bitacora de auditoria ===
AUDITORIA [REGISTRADO] Estudiante [ID=1, Nombre=Ana Torres, Edad=20] - Agregado exitosamente: Ana Torres [Decorator: auditoria CREATE]
AUDITORIA [REGISTRADO] Estudiante [ID=2, Nombre=Luis Perez, Edad=17] - Agregado exitosamente: Luis Perez [Decorator: auditoria CREATE]
AUDITORIA [ACTUALIZADO] Estudiante [ID=1, Nombre=Ana Torres Actualizada, Edad=21] - Actualizado exitosamente: ID 1 [Decorator: auditoria UPDATE]
AUDITORIA [ELIMINADO] Estudiante [ID=1, Nombre=Ana Torres Actualizada, Edad=21] - Eliminado exitosamente: Ana Torres Actualizada (ID 1) [Decorator: auditoria DELETE]

=== Strategy: busqueda dinamica ===
Busqueda por ID criterio=3
Estudiante [ID=3, Nombre=Carlos Andrade, Edad=22]
Busqueda por Nombre criterio=Caro
Estudiante [ID=4, Nombre=Carolina Mena, Edad=23]
```

## Reflexion tecnica

Observer aporta mantenibilidad porque desacopla las acciones del CRUD de los
componentes que reaccionan a ellas. Si se necesita enviar correo, registrar en un
archivo o mostrar una alerta, se agrega otro observador sin modificar el CRUD
base.

Strategy aporta flexibilidad porque encapsula algoritmos de busqueda
intercambiables. El cliente principal solo selecciona una estrategia; no necesita
conocer ni duplicar la logica de busqueda.
