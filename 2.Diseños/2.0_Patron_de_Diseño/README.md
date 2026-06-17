# Patrón de Diseño y Arquitectura

Esta carpeta contiene los diagramas y documentos relacionados con la arquitectura por capas y el uso del patrón **Builder** en IronClad Box.

## Diagramas vigentes

| Archivo | Descripción |
| --- | --- |
| `arquitectura_capasv2.puml` | Arquitectura V2 alineada con login, roles, dashboards, MVC, Builder, DAO y MySQL/PDO |
| `arquitectura_capas.puml` | Arquitectura general del MVP con módulos Release 1 y Release 2 |

## Criterio actual

El diagrama vigente para defensa técnica de login, cédula, correo y navegación por rol es:

```text
arquitectura_capasv2.puml
```

El archivo `nav.js` se considera parte de la **capa de presentación**, porque renderiza el menú visible según el rol autenticado. No se modela como servicio de dominio.

## Documento complementario

La explicación textual del patrón Builder está en:

```text
3.Codigo/IRONBOX V1.0.0/PATRON_BUILDER.md
```
