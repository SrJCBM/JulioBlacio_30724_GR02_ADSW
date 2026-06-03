# U2T1 - CRUD Estudiante en arquitectura de tres capas

## Proposito

Disenar e implementar un CRUD base para estudiantes separando claramente la capa
de Presentacion, la capa de Logica de negocio, la capa de Datos y el modelo del
estudiante. El CRUD permite registrar, consultar, actualizar y eliminar
estudiantes con los campos `ID`, `Nombre` y `Edad`.

## Productos verificables

| Producto | Estado | Evidencia |
| --- | --- | --- |
| Proyecto base organizado en tres capas | Cumplido | Carpetas `presentacion`, `logica`, `datos` y `modelo`. |
| Diagrama o esquema de arquitectura | Cumplido | Archivo `diagrama_tres_capas_crud_estudiante.puml`. |
| Tabla de responsabilidades por capa | Cumplido | Seccion "Responsabilidades por capa". |
| Matriz de trazabilidad | Cumplido | Seccion "Matriz de trazabilidad". |
| Pruebas de registro, consulta, actualizacion y eliminacion | Cumplido | Clase `presentacion.PruebaCrudEstudiantes`. |

## Responsabilidades por capa

| Capa | Paquete | Responsabilidad | Clases |
| --- | --- | --- | --- |
| Presentacion | `U2T1.presentacion` | Capturar datos del usuario, invocar la logica y mostrar resultados. No accede a persistencia. | `CRUDEstudiantesGUI`, `PruebaCrudEstudiantes` |
| Logica de negocio | `U2T1.logica` | Validar datos, aplicar reglas academicas y coordinar operaciones CRUD. | `ControlEstudiante` |
| Datos | `U2T1.datos` | Gestionar la coleccion de estudiantes en memoria sin reglas de negocio. | `RepositorioEstudiante` |
| Modelo | `U2T1.modelo` | Representar la entidad del dominio con ID, Nombre y Edad. | `Estudiante` |

## Matriz de trazabilidad sugerida

Esta matriz relaciona cada requisito funcional de la ERS/SRS con el caso de uso,
la operacion del CRUD y los componentes implementados.

| Codigo | Requisito funcional ERS/SRS | Caso de uso | Operacion CRUD | Componentes implementados | Evidencia de prueba |
| --- | --- | --- | --- | --- | --- |
| RF-01 | El sistema debe permitir registrar un nuevo estudiante con ID, Nombre y Edad. | Agregar estudiante | Create | `CRUDEstudiantesGUI.onAgregar`, `ControlEstudiante.agregarEstudiante`, `RepositorioEstudiante.guardar`, `Estudiante` | `PruebaCrudEstudiantes`: bloque "RF-01 Registrar" |
| RF-02 | El sistema debe permitir actualizar los datos de un estudiante existente usando ID, Nombre y Edad. | Actualizar estudiante | Update | `CRUDEstudiantesGUI.onActualizar`, `ControlEstudiante.actualizarEstudiante`, `RepositorioEstudiante.actualizar` | `PruebaCrudEstudiantes`: bloque "RF-02 Actualizar" |
| RF-03 | El sistema debe permitir eliminar un estudiante seleccionado cuando corresponda segun la regla academica definida. | Eliminar estudiante | Delete | `CRUDEstudiantesGUI.onEliminar`, `ControlEstudiante.eliminarEstudiante`, `RepositorioEstudiante.eliminar` | `PruebaCrudEstudiantes`: bloque "RF-03 Eliminar" |
| RF-04 | El sistema debe permitir mostrar todos los estudiantes registrados con ID, Nombre y Edad. | Mostrar estudiantes | Read/List | `CRUDEstudiantesGUI.actualizarLista`, `ControlEstudiante.listarTodos`, `RepositorioEstudiante.listarTodos` | `PruebaCrudEstudiantes`: bloque "RF-04 Consultar" |
| RF-05 | El sistema debe validar datos obligatorios antes de guardar o actualizar cambios. | Validar datos del estudiante | Validacion | `ControlEstudiante.validarDatos`, `ControlEstudiante.agregarEstudiante`, `ControlEstudiante.actualizarEstudiante` | Mensajes de error ante ID/Edad invalidos o Nombre vacio |

## Matriz de trazabilidad global integrada

| Requisito / entrada | U2T1 Arquitectura | U2T2 Patrones estructurales | U2T3 Patrones de comportamiento | Evidencia final | Control de coherencia |
| --- | --- | --- | --- | --- | --- |
| RF priorizados del CRUD | Se implementan operaciones base del estudiante: registrar, consultar, actualizar y eliminar. | Se adaptan entradas externas y se extienden funcionalidades del CRUD. | Se agregan eventos del CRUD y estrategias de busqueda. | Codigo, diagramas PlantUML y pruebas de consola. | Ningun patron debe aparecer sin requisito asociado. |
| RNF de mantenibilidad | Se separan responsabilidades por capas. | Se reducen cambios directos con Adapter y Decorator. | Se facilita el cambio de comportamiento con Observer y Strategy. | Justificacion tecnica en los README. | La solucion debe ser mas flexible, no mas compleja innecesariamente. |
| Arquitectura | Se definen Presentacion, Logica de negocio, Datos y Modelo. | Los patrones estructurales se insertan respetando las capas. | Los patrones de comportamiento se ubican principalmente en la logica de negocio. | Esquemas PlantUML actualizados. | La presentacion no debe asumir responsabilidades de datos o reglas complejas. |
| Diseno orientado a objetos | Se identifican clases, metodos y responsabilidades. | Se aplican patrones estructurales Adapter y Decorator. | Se aplican patrones de comportamiento Observer y Strategy. | Diagramas de clases/componentes. | Los diagramas deben coincidir con la implementacion. |
| Evidencias de funcionamiento | Se ejecuta el CRUD base. | Se demuestra adaptacion y extension. | Se demuestra notificacion y cambio de estrategia. | Capturas, pruebas o demos de consola. | Cada evidencia debe vincularse a una tarea y requisito. |

## Diagrama PlantUML

El diagrama actualizado esta en:

```text
diagrama_tres_capas_crud_estudiante.puml
```

Resumen del flujo:

```text
Presentacion -> Logica de negocio -> Datos -> Modelo
CRUDEstudiantesGUI -> ControlEstudiante -> RepositorioEstudiante -> Estudiante
```

## Criterios de aceptacion

| Criterio | Cumplimiento |
| --- | --- |
| La capa de presentacion no accede directamente a la persistencia. | Cumplido: `CRUDEstudiantesGUI` solo usa `ControlEstudiante`. |
| Las validaciones del estudiante estan en la logica de negocio. | Cumplido: `validarDatos` esta en `ControlEstudiante`. |
| Las operaciones CRUD estan vinculadas a requisitos de la ERS/SRS. | Cumplido: ver matriz RF-01 a RF-05. |
| El proyecto es comprensible, ejecutable y documentado. | Cumplido: README, PlantUML y prueba de consola. |

## Como ejecutar

Desde PowerShell, ubicarse en la carpeta padre de `U2T1`:

```powershell
cd "c:\Users\jcbla\Desktop\ESPE\Sexto Semestre\AlexanderToapanta_30724_G_ADSW\2.Diseños\2.0_Patron_de_Diseño"
```

Compilar:

```powershell
javac (Get-ChildItem "U2T1" -Recurse -Filter *.java | Select-Object -ExpandProperty FullName)
```

Ejecutar prueba de consola:

```powershell
java U2T1.presentacion.PruebaCrudEstudiantes
```

Ejecutar interfaz grafica:

```powershell
java U2T1.presentacion.CRUDEstudiantesGUI
```

## Evidencia esperada de consola

```text
=== RF-01 Registrar ===
Agregado exitosamente: Ana Torres
Agregado exitosamente: Luis Perez

=== RF-04 Consultar ===
Estudiante [ID=1, Nombre=Ana Torres, Edad=20]
Estudiante [ID=2, Nombre=Luis Perez, Edad=17]

=== RF-02 Actualizar ===
Actualizado exitosamente: ID 1

=== RF-03 Eliminar ===
Error: Regla academica - no se puede eliminar a 'Luis Perez' porque es menor de 18 anios (Edad: 17).
Eliminado exitosamente: Ana Torres Actualizada (ID 1)

=== Lista final ===
Estudiante [ID=2, Nombre=Luis Perez, Edad=17]
```
