# Diagramas de Componentes

Esta carpeta está destinada a los diagramas que describen la organización de componentes del sistema.

## Relación con la implementación

Los componentes principales de IronClad Box se agrupan por capas:

- **Views:** pantallas HTML y scripts Vanilla JS.
- **Controllers:** endpoints PHP por módulo.
- **Services:** reglas de negocio.
- **Builders:** construcción validada de entidades complejas.
- **DAO:** acceso a MySQL mediante PDO.
- **Models:** entidades de dominio.
- **Includes:** sesión, autenticación, conexión y utilidades compartidas.

## Referencias vigentes

Para entender los componentes actuales y sus dependencias, revisar:

```text
2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml
2.Diseños/2.3_Diagrama_de_Clases/diagramadeclasesv2.puml
```
