# Webservice público

Al publicar el formulario (`PUT /api/pqr/form/publish`), el sistema genera automáticamente los archivos necesarios para el webservice. No requiere autenticación.

## URLs

| URL | Descripción |
|---|---|
| `https://DOMINIO/ws/pqr` | Formulario público de radicación |
| `https://DOMINIO/ws/pqr/consulta` | Consulta de estado por número de radicado |
| `https://DOMINIO/ws/pqr/calificacion` | Calificación de la respuesta recibida |

## Radicación

El ciudadano accede, completa el formulario con los campos configurados y su PQR queda radicada en SAIA.

Endpoints de radicación:

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/pqr/webservice/saveDocument` | Radica PQR desde el formulario público |
| `POST` | `/api/pqr/captcha/saveDocument` | Radica PQR con validación CAPTCHA |

La respuesta contiene el número de radicado: `{"number": "PQR-2026-0001234"}`.

## Generación de archivos

Al publicar, `PqrFormService::publish()` invoca los generadores en `Services/controllers/generadoresWs/` que producen los archivos HTML/JS/PHP del formulario público. Los templates base están en `Services/controllers/templates/`.

## Calificación

Accesible desde el enlace enviado en el email de respuesta. El ciudadano completa la encuesta y el registro queda en `ft_pqr_calificacion`. El webservice de calificación se genera con `WebserviceCalificacion.php`.
