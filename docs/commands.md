# Comandos de consola

## Comandos disponibles

```bash
# Genera índices en BD para las tablas del módulo (ft_pqr, pqr_backups, etc.)
php bin/console app:generate:indexesPqr

# Genera archivos JSON de traducción para el módulo
php bin/console app:generate:json-translator-for-pqr
```

## Caché IA

```bash
# Invalidar caché de campos lookup (tras modificar el formulario)
php bin/console cache:pool:delete cache.app pqr_ia_lookup_fields
```
