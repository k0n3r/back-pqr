# Base de datos

## Tablas principales

| Tabla                 | Descripción                                            |
|-----------------------|--------------------------------------------------------|
| `ft_pqr`              | Formato principal — cada fila es una PQR radicada      |
| `ft_pqr_respuesta`    | Respuestas del funcionario al ciudadano                |
| `ft_pqr_calificacion` | Calificación del ciudadano a la respuesta              |
| `pqr_forms`           | Configuración del formulario (singleton por instancia) |
| `pqr_form_fields`     | Campos configurados en el formulario                   |
| `pqr_html_fields`     | Catálogo de tipos de campo disponibles                 |
| `pqr_notifications`   | Notificaciones por email configuradas                  |
| `pqr_noty_messages`   | Mensajes de las notificaciones                         |
| `pqr_history`         | Historial completo de cambios por PQR                  |
| `pqr_response_times`  | Tiempos de respuesta configurados por campo/tipo       |
| `pqr_balancer`        | Grupos del balanceador de carga                        |
| `pqr_backups`         | Respaldos JSON de datos de PQRs                        |

Las tablas `pqr_*` tienen entidades Doctrine en `Entity/` y repositorios en `Repository/`. Las tablas `ft_pqr*` usan Active Record legacy (extienden `ModelFormat`).

## Estados de `ft_pqr`

| Constante          | Valor         | Descripción                    |
|--------------------|---------------|--------------------------------|
| `ESTADO_PENDIENTE` | `'PENDIENTE'` | Radicada, sin asignar          |
| `ESTADO_INICIADO`  | `'INICIADO'`  | Asignada, sin trabajo iniciado |
| `ESTADO_PROCESO`   | `'PROCESO'`   | En gestión activa              |
| `ESTADO_TERMINADO` | `'TERMINADO'` | Respondida y cerrada           |

## Oportunidad de respuesta

| Constante                            | Descripción               |
|--------------------------------------|---------------------------|
| `OPORTUNO_PENDIENTES_SIN_VENCER`     | Abiertas dentro del plazo |
| `OPORTUNO_VENCIDAS_SIN_CERRAR`       | Abiertas y fuera de plazo |
| `OPORTUNO_CERRADAS_A_TERMINO`        | Cerradas dentro del plazo |
| `OPORTUNO_CERRADAS_FUERA_DE_TERMINO` | Cerradas fuera del plazo  |

## Niveles de severidad / impacto / frecuencia

| Constante                  | Valor |
|----------------------------|-------|
| `ESTADO_FRE_IMP_SEV_BAJO`  | `1`   |
| `ESTADO_FRE_IMP_SEV_MEDIO` | `2`   |
| `ESTADO_FRE_IMP_SEV_ALTO`  | `3`   |

## Tipos de historial (`pqr_history.tipo`)

| Constante                     | Descripción                          |
|-------------------------------|--------------------------------------|
| `TIPO_TAREA`                  | Creación o cierre de tarea           |
| `TIPO_NOTIFICACION`           | Email enviado                        |
| `TIPO_RESPUESTA`              | Respuesta al ciudadano               |
| `TIPO_CAMBIO_ESTADO`          | Cambio de estado                     |
| `TIPO_CAMBIO_VENCIMIENTO`     | Modificación de fecha de vencimiento |
| `TIPO_CALIFICACION`           | Calificación recibida                |
| `TIPO_MODIFICACION_TERCERO`   | Modificación de datos del ciudadano  |
| `TIPO_ERROR_DIAS_VENCIMIENTO` | Error al calcular vencimiento        |

## Migraciones

Las migraciones están en `Resources/migrations/`. Se ejecutan con:

```bash
php bin/console doctrine:migrations:migrate
```
