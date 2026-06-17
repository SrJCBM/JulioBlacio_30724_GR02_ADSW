# Diseño de Arquitectura

Esta carpeta conserva artefactos de arquitectura usados durante la evolución del proyecto IronClad Box.

## Arquitectura actual

La versión vigente de arquitectura por capas para la defensa final se encuentra en:

```text
2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml
```

## Arquitectura aplicada

IronClad Box usa:

- **PHP nativo** para controladores, servicios y DAO.
- **HTML, CSS y Vanilla JS** para vistas.
- **MVC** como estructura principal.
- **Builder** en la capa de lógica de negocio.
- **PDO** para acceso a datos.
- **MySQL** como base de datos activa.

## Nota

Los archivos históricos de esta carpeta pueden servir como evidencia de iteración, pero la referencia principal del estado actual es la arquitectura V2.
