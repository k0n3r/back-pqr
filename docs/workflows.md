# Flujos de trabajo

## 1. Administrador: configurar y publicar el formulario

1. Accede a `/dashboard/pqr/configuracion`.
2. **Crea los campos** del formulario (`POST /api/pqr/formField`):
   - Selecciona el tipo: `Text`, `Textarea`, `Date`, `Select`, `File`, `Autocomplete`, `Dependencia`, `Localidad`, etc.
   - Configura etiqueta, si es obligatorio, si es visible en modo anónimo y el orden.
3. **Reordena** los campos arrastrando (`PUT /api/pqr/form/sortFields`).
4. **Configura notificaciones** (`POST /api/pqr/notification`): qué funcionarios reciben email ante cada evento.
5. **Define tiempos de respuesta** por tipo de PQR (`PUT /api/pqr/responseTimes`). Ej.: "Petición → 10 días hábiles", "Queja → 15 días".
6. **Activa el balanceador** si se quiere distribución automática (`PUT /api/pqr/form/balancer`): asigna la PQR al grupo de funcionarios menos cargado para ese tipo.
7. **Publica el formulario** (`PUT /api/pqr/form/publish`): el sistema genera los archivos del webservice y deja disponible `https://DOMINIO/ws/pqr`.

## 2. Ciudadano: radicar una PQR

1. Accede a `https://DOMINIO/ws/pqr` (sin autenticación).
2. Completa el formulario con los campos configurados por el administrador.
3. Si el formulario lo permite, puede marcar **Anónimo**: los campos de identificación se ocultan o se vuelven opcionales según la configuración de cada campo.
4. Adjunta archivos si aplica.
5. Resuelve el CAPTCHA (si está habilitado).
6. Envía el formulario (`POST /api/pqr/captcha/saveDocument` o `POST /api/pqr/webservice/saveDocument`).
7. El sistema retorna el **número de radicado**. Con ese número, el ciudadano puede consultar el estado en `https://DOMINIO/ws/pqr/consulta`.

**Lo que ocurre al radicar:**

- Se crea el registro en `ft_pqr` con estado `PENDIENTE`.
- Se genera un respaldo JSON en `pqr_backups`.
- Se calcula `sys_fecha_vencimiento` según el tipo de PQR y `pqr_response_times`.
- El balanceador asigna el documento si está activo.
- Se notifica por email a los funcionarios configurados en `pqr_notifications`.

## 3. Funcionario: gestionar y responder

1. Accede a la lista de PQRs asignadas en el dashboard.
2. Abre la PQR y consulta:
   - Datos del ciudadano (`GET /api/pqr/{idft}/externalUser`).
   - Historial con timeline (`GET /api/pqr/{idft}/history`).
3. El estado avanza automáticamente según las tareas:
   - Tarea creada → `INICIADO`.
   - Tarea con cambio de estado → `PROCESO`.
   - Tarea finalizada → `TERMINADO`.
   - También se puede avanzar manualmente con `PUT /api/pqr/{idft}/finish`.
4. Para responder, accede al formulario de respuesta y carga:
   - **Asunto** y **contenido** (editor HTML).
   - **Tipo de distribución**: recogida + entrega, solo entrega, sin mensajería o email.
   - **Despedida**: Atentamente, Cordialmente u Otra.
   - **¿Solicitar encuesta?**: si se activa, el sistema envía el enlace de calificación al ciudadano.
5. Al guardar la respuesta, el sistema:
   - Valida los emails.
   - Registra la respuesta en `ft_pqr_respuesta`.
   - Envía email al ciudadano.
   - Registra el evento en `pqr_history` como `TIPO_RESPUESTA`.
   - Marca la PQR como `TERMINADO` y calcula si fue oportuna o vencida (`sys_oportuno`).

## 4. Ciudadano: calificar y consultar

- **Calificación:** Desde el enlace en el email de respuesta (`https://DOMINIO/ws/pqr/calificacion`), el ciudadano completa la encuesta. El registro queda en `ft_pqr_calificacion`.
- **Consulta de estado:** Accediendo a `https://DOMINIO/ws/pqr/consulta` e ingresando el número de radicado, el ciudadano ve el estado actual y el historial de eventos con timeline.

## 5. Flujo técnico resumido (radicación)

```
Ciudadano  →  POST /api/pqr/captcha/saveDocument
                └─ CaptchaController valida CAPTCHA
                └─ SaveDocument (core SAIA) crea ft_pqr
                    └─ FtPqr::afterRad()
                        ├─ createTaskFromDataTemp()   → genera tarea si aplica
                        ├─ postDocumentRad()          → post-radicación
                        └─ sendDocumentsByEmail()     → notifica funcionarios
                └─ FtPqrService::createBackup()       → respaldo JSON en pqr_backups
                └─ PqrSubscriber::onTaskCreated()     → historial + estado INICIADO
                └─ Calcula sys_fecha_vencimiento      → pqr_response_times
                └─ Balanceador (si activo)            → asigna a funcionario
         ←  {"number": "PQR-2026-0001234"}
```
