# Instalación

El módulo está incluido en el repositorio principal de SAIA como submódulo Git.

## Pasos

```bash
# 1. Ejecutar migraciones Doctrine
php bin/console doctrine:migrations:migrate

# 2. Generar índices en BD
php bin/console app:generate:indexesPqr

# 3. Compilar frontend
cd public/views/modules/pqr
npm install
npm run build
```

## Paso manual obligatorio — Publicar el formulario PQR

Las migraciones crean los formatos hijos `pqr_respuesta` y `pqr_calificacion`, pero **no crean el formato
padre `pqr`** (`nombre_tabla = ft_pqr`): ese formato se define desde la Administración, no por migración.

Antes de usar el módulo (y antes de correr `app:format:refresh` sobre todos los formatos), entrar a
`views/modules/pqr/dist/pqr/index.html`, configurar el formulario y presionar **Publicar**
(`PUT /form/publish` → `PqrFormController::publish()` → `PqrFormService::publish()`). Ese paso crea el
formato `pqr` y vincula `pqr_respuesta`/`pqr_calificacion` como sus hijos. Sin este paso, `pqr_respuesta`
queda sin padre resuelto y `format:refresh` falla con `Debe definir la funcion en el campo pqr`.

## Verificación

```bash
# Validar mapeo ORM
php bin/console doctrine:schema:validate

# Limpiar caché
php bin/console cache:clear
```

## Requisitos

- Symfony 7.4 / PHP 8.3+
- MySQL 8 (driver personalizado `CustomMySQLDriver`)
- Node.js 18+ (para compilar frontend)
- Bundle `ia` opcional — el módulo funciona sin él
