# Características especiales

## Formulario 100% dinámico

El administrador diseña los campos desde la interfaz sin escribir código. Puede agregar, reordenar, activar/desactivar y configurar cada campo de forma independiente. Al publicar, el sistema regenera automáticamente el frontend del webservice.

## Anonimato

Si `PqrForm.show_anonymous = 1`, el formulario público muestra un checkbox "Anónimo". Cada campo tiene un atributo `anonymous` que indica si debe ser visible o requerido cuando el usuario elige esta opción. Al radicar de forma anónima, el sistema crea un tercero sin identificación personal.

## Balanceador de carga

Si `enable_balancer = 1`, al radicar una PQR el sistema consulta `pqr_balancer` para el tipo de PQR y selecciona el grupo con menor carga de trabajo. Esto evita que un solo funcionario reciba todas las PQRs de un mismo tipo.

## Tiempos de respuesta

Configurables por tipo de PQR en `pqr_response_times`. Al radicar, se calcula `sys_fecha_vencimiento` automáticamente. El campo `enable_con_days` determina si se cuentan **días corridos** (`1`) o **días hábiles** (`0`). Si la PQR no se responde en el plazo, se marca como `OPORTUNO_VENCIDAS_SIN_CERRAR` y queda visible con prioridad en el dashboard.

## Historial y trazabilidad completa

Cada evento relevante de una PQR queda registrado en `pqr_history` con fecha exacta y el funcionario responsable. Los tipos de evento van desde creación de tarea hasta modificaciones de datos del ciudadano. Este historial se expone como un **timeline visual** en la interfaz.

## Respaldos JSON

Cada PQR tiene un respaldo en `pqr_backups` con una copia completa de sus datos en el momento de la radicación. Útil para auditorías y recuperación de datos.

## Canales de recepción

Configurable por el administrador desde `PUT /api/pqr/form/receivingchannels`. Los canales disponibles son: `WEB`, `EMAIL`, `FÍSICO`, `TELEFÓNICO`. El canal queda registrado en `ft_pqr.canal_recepcion`.

## Encuesta de satisfacción

Al responder una PQR, el funcionario puede optar por solicitar una calificación al ciudadano. El sistema envía un email con enlace a `https://DOMINIO/ws/pqr/calificacion`. La calificación queda en `ft_pqr_calificacion` y se asocia al expediente.

## Severidad / impacto / frecuencia

Las PQRs pueden clasificarse con tres niveles para cada dimensión:

| Constante | Valor |
|---|---|
| `ESTADO_FRE_IMP_SEV_BAJO` | `1` |
| `ESTADO_FRE_IMP_SEV_MEDIO` | `2` |
| `ESTADO_FRE_IMP_SEV_ALTO` | `3` |
