# Changelog

### 2026-08 - Correcciones

| Fecha | Cambio | Detalles |
|-------|--------|----------|
| 2026-08-26 | Fix hora de la fecha límite/tarea automática | `FtPqrService::getDateForType()` copiaba la hora exacta de radicación del documento (`$Created`) al calcular la fecha límite, en vez de fijar fin de día. Si la PQR se radicaba de madrugada (~00:00), la fecha límite y la tarea automática del balanceador (`getTaskDefaultData()`) quedaban con hora cercana a medianoche del último día, dejando al responsable sin tiempo real para gestionarla. `getTaskDefaultEndDate()` además sumaba 30 minutos fijos (`PT30M`) sobre esa hora ya errónea, resultando en el caso reportado: tarea vencida a las 00:30:00 del día límite. Ahora `getDateForType()` fija siempre `23:59:59` y `getTaskDefaultEndDate()` ya no le suma el colchón de 30 minutos (redundante al estar ya al final del día). Afecta tanto `sys_fecha_vencimiento`/`fecha_limite` del documento como la tarea del balanceador. |
| 2026-08-26 | Tests de regresión | `FtPqrServiceDeadlineTest` (Integration): verifica que `getDateForType()` retorna `23:59:59` sin importar la hora de radicación (días calendario) y que `getTaskDefaultEndDate()` ya no modifica una fecha de fin de día ya calculada. |

---
