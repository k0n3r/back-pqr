# Comandos de consola

## Comandos disponibles

```bash
# Genera índices en BD para las tablas del módulo (ft_pqr, pqr_backups, etc.)
php bin/console app:generate:indexesPqr

# Genera archivos JSON de traducción para el módulo
php bin/console app:generate:jsonTranslatorForPQR
```

## Importación masiva de PQRs

```bash
php bin/console app:pqr:import-from-file <archivo>
```

Importa PQRs en bloque desde un archivo `.xlsx`, `.ods` o `.csv`. Crea un documento `ft_pqr` por cada fila.

| Opción          | Descripción                          | Default |
|-----------------|--------------------------------------|---------|
| `file`          | Ruta al archivo (requerido)          | —       |
| `--delimiter`   | Separador de columnas (solo CSV)     | `,`     |
| `--skip-rows N` | Filas iniciales a omitir (cabeceras) | `0`     |
| `--preview N`   | Muestra N registros sin importar     | —       |

### Ejemplos

```bash
# Vista previa de los primeros 5 registros
php bin/console app:pqr:import-from-file archivo.xlsx --preview 5

# Importar CSV con punto y coma, omitiendo la primera fila de cabecera
php bin/console app:pqr:import-from-file datos.csv --delimiter ";" --skip-rows 1
```

El comando realiza GC cada 10 registros y genera logs detallados. Las ciudades se resuelven por caché para optimizar rendimiento.

## Caché IA

```bash
# Invalidar caché de campos lookup (tras modificar el formulario)
php bin/console cache:pool:delete cache.app pqr_ia_lookup_fields
```
