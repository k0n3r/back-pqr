# Eventos

## Eventos del formulario

Disparados por `PqrFormFieldService` al gestionar campos. Permiten que otros componentes reaccionen a cambios en la configuración del formulario.

| Evento | Cuándo se dispara |
|---|---|
| `PqrFormFieldCreatedEvent` | Al crear un campo en el formulario |
| `PqrFormFieldUpdateEvent` | Al actualizar un campo |
| `PqrFormFieldDeleteEvent` | Al eliminar un campo |

Todos extienden `PqrFormFieldEvent` (clase base en `Event/`).

## Suscriptor `PqrSubscriber`

Escucha eventos del sistema SAIA y actualiza el estado de la PQR automáticamente:

| Evento escuchado | Acción |
|---|---|
| `TaskCreatedEvent` | Registra creación de tarea en historial; avanza estado a `INICIADO` |
| `TaskDeletedEvent` | Registra eliminación de tarea en historial |
| `TaskStatusCreatedEvent` | Registra cambio de estado de tarea; puede avanzar el estado a `PROCESO` |
| `SentMessageEvent` | Registra email enviado en historial como `TIPO_NOTIFICACION` |

`PqrSubscriber` reside en `EventSubscriber/PqrSubscriber.php` y usa `PqrHistoryService` para persistir los registros en `pqr_history`.
