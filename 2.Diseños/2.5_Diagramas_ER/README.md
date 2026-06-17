# Diagramas Entidad-Relación

Esta carpeta contiene o referencia los artefactos relacionados con el modelo de datos de IronClad Box.

## Modelo de datos implementado

La base activa del proyecto es **MySQL**. Los scripts de creación y datos demo están en:

```text
3.Codigo/IRONBOX V1.0.0/database/schema_mysql.sql
3.Codigo/IRONBOX V1.0.0/database/seed_mysql.sql
```

Tablas principales del sistema:

- `usuarios`
- `atletas`
- `entrenadores`
- `clases`
- `reservas`
- `membresias`
- `progreso_atletas`
- `mensajes`
- `reportes`

## Acceso a datos

El acceso a estas tablas no se hace desde las vistas ni desde los controladores directamente. Cada módulo usa su clase DAO con **PDO**, manteniendo la separación por capas.

## Referencias

Para revisar cómo el modelo de datos se conecta con servicios y controladores, ver:

```text
2.Diseños/2.0_Patron_de_Diseño/arquitectura_capasv2.puml
2.Diseños/2.3_Diagrama_de_Clases/diagramadeclasesv2.puml
3.Codigo/IRONBOX V1.0.0/trazabilidad_ironclad.md
```
