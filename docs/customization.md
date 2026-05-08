# Personalización

## `PqrCustomizable`

La clase `Services/Customizable/PqrCustomizable.php` es la base extensible del módulo. Permite agregar funcionalidad específica por cliente sin modificar el core del bundle.

Extiende esta clase para sobreescribir comportamientos puntuales del ciclo de vida de la PQR.

## Integración IA condicional

Los servicios IA (`IA/`) se cargan condicionalmente solo si el bundle `ia` está instalado. Esto se controla en `Resources/config/ia_services.php` e `ia_routes.php`, de modo que el módulo funciona de forma completa sin IA.

## Modelo del agente IA

El modelo del agente `ia_pqr` se configura sin tocar código:

```env
# .env.local
IA_PQR_MODEL=claude-sonnet-4-6
```

Ver [ia-integration.md](ia-integration.md) para el detalle completo.

## Campos del formulario

El formulario es 100% configurable desde la interfaz de administración. Ver [workflows.md](workflows.md) para el flujo de configuración y publicación.

## Migraciones personalizadas

Las migraciones propias del módulo están en `Resources/migrations/`. Usan los traits `TMigrations` y `TDependencyReport` para operaciones comunes (crear índices, agregar columnas de reporte por dependencia).
