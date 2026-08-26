# PQR — Ciclo de vida y estados

---

## Estados

| Estado | Constante | Descripción |
|--------|-----------|-------------|
| Pendiente | `ESTADO_PENDIENTE` | Radicada por el ciudadano. Sin asignar aún. |
| Iniciado | `ESTADO_INICIADO` | Asignada a un funcionario. Sin trabajo iniciado. |
| Proceso | `ESTADO_PROCESO` | En gestión activa. |
| Terminado | `ESTADO_TERMINADO` | Respondida y cerrada. |

---

## Oportunidad de respuesta

| Constante | Descripción |
|-----------|-------------|
| `OPORTUNO_PENDIENTES_SIN_VENCER` | Abiertas dentro del plazo |
| `OPORTUNO_VENCIDAS_SIN_CERRAR` | Abiertas y fuera del plazo |
| `OPORTUNO_CERRADAS_A_TERMINO` | Cerradas dentro del plazo |
| `OPORTUNO_CERRADAS_FUERA_DE_TERMINO` | Cerradas fuera del plazo |

---

## Flujo típico

```
Ciudadano radica (ws/pqr o WhatsApp/MCP)
         │
         ▼
   PENDIENTE
   (notificación email a ciudadano)
         │
   Administrador asigna funcionario
         │
         ▼
   INICIADO
         │
   Funcionario comienza gestión
         │
         ▼
   PROCESO
         │
   Funcionario responde → PqrRespuesta creada
   (email de respuesta al ciudadano)
         │
         ▼
   TERMINADO
   (URL de calificación enviada al ciudadano)
```

---

## Historial de cambios

Cada cambio de estado, asignación o comentario queda registrado en `pqr_history`. El historial es
inmutable y se muestra al funcionario en la interfaz de gestión.

---

## Tiempos de respuesta

Configurables por tipo de PQR en `PqrResponseTime`. Cuando se cumple el plazo sin respuesta, la PQR
pasa a `OPORTUNO_VENCIDAS_SIN_CERRAR`. Los reportes de oportunidad usan estas fechas para calcular
el cumplimiento.

`FtPqrService::getDateForType()` calcula la fecha límite sumando los días configurados (calendario o
hábiles, según `PqrForm::isEnabledCalendarDays()`) a la fecha de radicación, y siempre fija la hora en
**23:59:59** — el responsable puede gestionar la PQR hasta el final del último día. Esta fecha se usa
tanto para `sys_fecha_vencimiento`/`fecha_limite` del documento como para la tarea automática que crea
el balanceador (`getTaskDefaultData()`).

---

## Balanceador de carga

El `PqrBalancer` distribuye PQRs automáticamente entre funcionarios según grupos configurados. Cada
grupo define qué funcionarios reciben PQRs del tipo asignado.

---

## Notificaciones

`PqrNotificationService` gestiona los emails del ciclo de vida:

| Evento | Destinatario |
|--------|-------------|
| Radicación | Ciudadano (confirmación + número de radicado) |
| Respuesta | Ciudadano (contenido de la respuesta + URL calificación) |
| Asignación | Funcionario asignado |
| Vencimiento | Funcionario responsable y supervisores |
