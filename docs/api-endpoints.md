# Endpoints REST

Prefijo base: `/api/pqr`. Requieren autenticación SAIA (firewall `main`), excepto el webservice público.

## Formato PQR

| Método | Ruta                                 | Descripción                                  |
|--------|--------------------------------------|----------------------------------------------|
| `GET`  | `/api/pqr/{idft}/history`            | Historial de cambios de la PQR               |
| `GET`  | `/api/pqr/{idft}/externalUser`       | Datos del ciudadano                          |
| `POST` | `/api/pqr/{idft}/externalUser`       | Actualiza datos del ciudadano                |
| `GET`  | `/api/pqr/{idft}/dataToLoadResponse` | Datos para cargar el formulario de respuesta |
| `GET`  | `/api/pqr/{idft}/dateForType`        | Fecha de vencimiento por tipo                |
| `GET`  | `/api/pqr/{idft}/valuesForType`      | Valores disponibles por tipo                 |
| `PUT`  | `/api/pqr/{idft}/updateType`         | Cambia el tipo de PQR                        |
| `PUT`  | `/api/pqr/{idft}/finish`             | Finaliza la PQR                              |

## Respuestas

| Método | Ruta                                           | Descripción                                 |
|--------|------------------------------------------------|---------------------------------------------|
| `GET`  | `/api/pqr/answers/{idft}/requestSurveyByEmail` | Envía encuesta de satisfacción al ciudadano |

## Configuración del formulario

| Método | Ruta                                  | Descripción                               |
|--------|---------------------------------------|-------------------------------------------|
| `GET`  | `/api/pqr/form/setting`               | Configuración actual                      |
| `GET`  | `/api/pqr/form/textFields`            | Campos de tipo texto disponibles          |
| `GET`  | `/api/pqr/form/responseSetting`       | Config de respuesta                       |
| `PUT`  | `/api/pqr/form/publish`               | Publica el formulario (genera webservice) |
| `PUT`  | `/api/pqr/form/sortFields`            | Reordena los campos                       |
| `PUT`  | `/api/pqr/form/updateSetting`         | Actualiza configuración general           |
| `PUT`  | `/api/pqr/form/updateResponseSetting` | Actualiza configuración de respuesta      |
| `PUT`  | `/api/pqr/form/balancer`              | Activa/desactiva balanceador              |
| `PUT`  | `/api/pqr/form/filterReport`          | Filtro de reporte por dependencia         |
| `PUT`  | `/api/pqr/form/receivingchannels`     | Canales de recepción habilitados          |

## Campos del formulario

| Método   | Ruta                               | Descripción        |
|----------|------------------------------------|--------------------|
| `POST`   | `/api/pqr/formField`               | Crea un campo      |
| `PUT`    | `/api/pqr/formField/{id}`          | Actualiza un campo |
| `PUT`    | `/api/pqr/formField/{id}/active`   | Activa el campo    |
| `PUT`    | `/api/pqr/formField/{id}/inactive` | Desactiva el campo |
| `DELETE` | `/api/pqr/formField/{id}`          | Elimina el campo   |

## Notificaciones

| Método   | Ruta                         | Descripción                       |
|----------|------------------------------|-----------------------------------|
| `POST`   | `/api/pqr/notification`      | Crea notificación por email       |
| `PUT`    | `/api/pqr/notification/{id}` | Actualiza notificación            |
| `DELETE` | `/api/pqr/notification/{id}` | Elimina notificación              |
| `PUT`    | `/api/pqr/notyMessage/{id}`  | Actualiza mensaje de notificación |

## Tiempos de respuesta y balanceador

| Método | Ruta                                | Descripción                          |
|--------|-------------------------------------|--------------------------------------|
| `GET`  | `/api/pqr/responseTimes/field/{id}` | Tiempos configurados para un campo   |
| `PUT`  | `/api/pqr/responseTimes`            | Actualiza tiempos de respuesta       |
| `GET`  | `/api/pqr/balancer/field/{id}`      | Grupos del balanceador para un campo |
| `PUT`  | `/api/pqr/balancer`                 | Actualiza grupos del balanceador     |

## Utilidades

| Método | Ruta                                    | Descripción                        |
|--------|-----------------------------------------|------------------------------------|
| `GET`  | `/api/pqr/searchByNumber`               | Busca PQR por número de radicado   |
| `GET`  | `/api/pqr/historyForTimeline`           | Historial formateado para timeline |
| `GET`  | `/api/pqr/decrypt`                      | Desencripta datos del ciudadano    |
| `GET`  | `/api/pqr/contentDependencia`           | Contenido de una dependencia       |
| `GET`  | `/api/pqr/components/autocomplete/list` | Opciones autocomplete              |
| `GET`  | `/api/pqr/components/autocomplete/find` | Búsqueda autocomplete              |
| `GET`  | `/api/pqr/structure/dataViewIndex`      | Estructura y tipos de PQR          |

## Webservice público (sin autenticación)

| Método | Ruta                               | Descripción                            |
|--------|------------------------------------|----------------------------------------|
| `POST` | `/api/pqr/webservice/saveDocument` | Radica PQR desde el formulario público |
| `POST` | `/api/pqr/captcha/saveDocument`    | Radica PQR con validación CAPTCHA      |
