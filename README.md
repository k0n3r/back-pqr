# Módulo PQR

Gestión integral de Peticiones, Quejas y Reclamos (PQR) en SAIA. Permite configurar formularios dinámicos, publicarlos como webservice público, gestionar el ciclo de vida de cada PQR y responderla con trazabilidad completa.

## Quick-start

```bash
# 1. Ejecutar migraciones
php bin/console doctrine:migrations:migrate

# 2. Generar índices en BD
php bin/console app:generate:indexesPqr

# 3. Compilar frontend
cd public/views/modules/pqr && npm install && npm run build
```

## Documentación

| Sección | Archivo |
|---|---|
| ¿Qué hace este módulo? | [docs/overview.md](docs/overview.md) |
| Arquitectura general | [docs/architecture.md](docs/architecture.md) |
| Estructura de directorios | [docs/directory-structure.md](docs/directory-structure.md) |
| Instalación | [docs/installation.md](docs/installation.md) |
| Base de datos | [docs/database.md](docs/database.md) |
| Endpoints REST | [docs/api-endpoints.md](docs/api-endpoints.md) |
| Comandos de consola | [docs/commands.md](docs/commands.md) |
| Flujos de trabajo | [docs/workflows.md](docs/workflows.md) |
| Características especiales | [docs/features.md](docs/features.md) |
| Eventos | [docs/events.md](docs/events.md) |
| Integración IA | [docs/ia-integration.md](docs/ia-integration.md) |
| Frontend | [docs/frontend.md](docs/frontend.md) |
| Webservice público | [docs/webservice.md](docs/webservice.md) |
| Personalización | [docs/customization.md](docs/customization.md) |

---

**Autor:** Andrés Agudelo — [andres.agudelo@saiasoftware.com](mailto:andres.agudelo@saiasoftware.com)  
**Licencia:** Propietaria — [SAIA Software](https://www.saiasoftware.com/)
