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
