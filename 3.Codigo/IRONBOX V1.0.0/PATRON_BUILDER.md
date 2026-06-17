# Patrón Builder en IronClad Box

Este documento explica cómo se usa el patrón creacional **Builder** dentro de IronClad Box V2.

Código de referencia:

```text
3.Codigo/IRONBOX V1.0.0/
```

Diagramas de apoyo:

- `2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml`
- `2.Diseños/2.3_Diagrama_de_Clases/diagramadeclasesv2.puml`

## 1. Problema que resuelve

Las entidades principales del sistema tienen reglas que no conviene mezclar con controladores ni DAO. Por ejemplo:

- Un usuario necesita nombre, cédula válida, correo válido, rol permitido, estado permitido y contraseña hasheada.
- Una clase necesita día, hora, duración, cupo máximo, cupos disponibles y entrenador.
- Una membresía necesita tipo, precio, estado, atleta y vencimiento calculado.
- Un registro de progreso permite campos opcionales, pero no puede quedar vacío.
- Un reporte necesita filtros, datos consolidados y formato de salida.

El **Builder** centraliza esa construcción paso a paso. Así, el DAO recibe entidades ya validadas y se dedica únicamente a persistir datos con **PDO**.

## 2. Ubicación en la arquitectura

```text
View HTML + Vanilla JS
  -> Controller PHP
    -> Service
      -> Builder
        -> Entidad válida
      -> DAO con PDO
        -> MySQL
```

El Builder vive en `builders/` y es invocado desde `services/`.

| Builder | Entidad | Responsabilidad principal |
| --- | --- | --- |
| `UsuarioBuilder` | `Usuario` | Valida cédula, correo, rol, estado y contraseña |
| `ClaseBuilder` | `Clase` | Valida agenda, cupos y entrenador |
| `MembresiaBuilder` | `Membresia` | Valida plan, estado, atleta y vencimiento |
| `RegistroProgresoBuilder` | `RegistroProgreso` | Maneja WODs con campos opcionales |
| `ReporteBuilder` | `Reporte` | Construye reportes filtrados y formateados |

## 3. Excepciones conscientes

No todo debe usar Builder.

- `Auth.php` no usa Builder porque es un helper de sesión PHP.
- `nav.js` no usa Builder porque pertenece a la capa de presentación y solo renderiza navegación.
- `ComunicacionService` no usa Builder porque `Mensaje` es una entidad simple y directa.

Esta decisión evita sobreingeniería y mantiene el patrón en entidades donde sí hay validaciones progresivas o datos derivados.

## 4. Mecanismos del patrón

Cada método configura una parte del objeto y devuelve `self`, permitiendo una interfaz fluida:

```php
$usuario = (new UsuarioBuilder())
    ->configurarNombre($datos['nombre'])
    ->configurarCedula($datos['cedula'])
    ->configurarCorreo($datos['correo'])
    ->definirContrasena($datos['contrasena'])
    ->asignarRol($datos['rol'])
    ->definirEstado('Activo')
    ->construir();
```

Cada método valida su propio dato. Si algo falla, lanza una excepción antes de llegar al DAO.

Ejemplos:

- `configurarCedula()` aplica el algoritmo de cédula ecuatoriana.
- `configurarCorreo()` valida formato de correo.
- `asignarRol()` solo acepta `Administrador`, `Entrenador` o `Atleta`.
- `definirCupoMaximo()` impide exceder la capacidad del box.
- `definirEstado()` bloquea estados no permitidos.

El método `construir()` revisa que la entidad esté completa y consistente. Solo entonces devuelve el modelo de dominio.

## 5. Ejemplos por requerimiento

### Usuarios: REQ001 a REQ006

`UsuarioBuilder` cubre la construcción segura de cuentas:

- Nombre obligatorio.
- Cédula ecuatoriana válida.
- Correo válido.
- Contraseña hasheada con `password_hash`.
- Rol dentro del catálogo permitido.
- Estado `Activo` o `Inactivo`.

`UsuarioService` complementa al Builder validando unicidad de correo y cédula contra `UsuarioDAO`.

### Membresías: REQ007 a REQ012

`MembresiaBuilder` permite crear, editar y renovar membresías sin duplicar reglas:

```php
$membresia = (new MembresiaBuilder())
    ->asignarAtleta((int) $datos['idAtleta'])
    ->configurarPlan($datos['tipo'], (float) $datos['precio'])
    ->definirFechaInicio($datos['fechaInicio'])
    ->definirEstado($datos['estado'])
    ->calcularFechaVencimiento($datos['fechaInicio'])
    ->construir();
```

Para pagos, `marcarComoPagadoDesde()` agrupa tres reglas:

- estado `Pagado`,
- fecha de inicio actualizada,
- vencimiento extendido a 30 días.

### Clases: REQ013 a REQ018

`ClaseBuilder` valida:

- día y hora obligatorios,
- duración positiva,
- cupo máximo mayor a cero,
- cupo máximo dentro de la capacidad del box,
- cupos disponibles coherentes,
- entrenador asignado.

Luego `ClaseService` aplica reglas cruzadas como solapamiento de horarios y disponibilidad del entrenador.

### Progreso deportivo

`RegistroProgresoBuilder` permite que tiempo, repeticiones, peso, puntuación y notas sean opcionales según el WOD. Sin embargo, bloquea registros sin datos deportivos mínimos.

Esto permite flexibilidad sin perder coherencia.

### Reportes

`ReporteBuilder` construye reportes con:

- tipo de reporte,
- rango de fechas,
- datos consolidados,
- columnas y resumen,
- salida para vista, CSV o PDF.

Así, `ReporteService` no duplica lógica de formateo entre pantalla y exportación.

## 6. Beneficios concretos

- **Separación de responsabilidades:** el Controller recibe, el Service coordina, el Builder valida y el DAO persiste.
- **Integridad del dominio:** no se crean entidades incompletas o inválidas.
- **Reutilización:** la misma construcción sirve para alta, edición y renovación.
- **Defensa técnica:** las validaciones no dependen del HTML; también existen en backend.
- **Mantenibilidad:** las reglas de construcción están concentradas en clases específicas.

## 7. Resumen para defensa

El patrón **Builder** en IronClad Box no es decorativo. Es la pieza que transforma datos crudos de formularios en entidades válidas antes de llegar a la base de datos. Esto permite demostrar arquitectura limpia, reglas de negocio encapsuladas y consistencia entre los módulos principales del sistema.
