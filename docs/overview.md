# ¿Qué hace este módulo?

## Gestión del ciclo de vida de PQRs

Permite radicar, clasificar, asignar y responder PQRs con historial completo de cambios. Cada PQR pasa por los estados **PENDIENTE → INICIADO → PROCESO → TERMINADO** con tiempos de respuesta configurables.

## Formulario dinámico configurable

El administrador diseña el formulario desde la interfaz SAIA: agrega campos de distintos tipos (texto, select, fecha, archivo, autocomplete, dependencia, localidad, etc.), los ordena y lo publica. El sistema genera automáticamente el webservice público y los archivos de frontend necesarios.

## Webservice público

Una vez publicado, el formulario queda disponible en `https://DOMINIO/ws/pqr`. Los ciudadanos ingresan, completan el formulario y su PQR queda radicada en SAIA sin necesidad de autenticación.

## Respuestas y calificaciones

El funcionario responde la PQR, el sistema envía notificación al ciudadano con la respuesta y puede solicitar una encuesta de satisfacción. La calificación queda asociada al expediente.

## Integración IA

Chat conversacional sobre PQRs, herramientas de estadísticas para el agente IA, y exposición del formato PQR como tool del servidor MCP para radicación autónoma por agentes.
