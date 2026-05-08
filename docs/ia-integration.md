# Integración IA

La integración IA se carga condicionalmente: solo si el bundle `ia` está instalado. Los archivos de configuración condicional están en `Resources/config/ia_routes.php` e `ia_services.php`. El módulo funciona de forma completa sin IA.

## Variable de entorno del modelo

| Variable | Default | Descripción |
|---|---|---|
| `IA_PQR_MODEL` | `claude-haiku-4-5-20251001` | Modelo del agente `ia_pqr`. Configurable en `.env.local` sin tocar código. |

## Agente dedicado `ia_pqr`

PQR tiene su propio agente IA (`ia_pqr`) con herramientas exclusivas. Estas herramientas **no están disponibles** en el agente genérico `ia_orchestrator`, evitando que aparezcan en conversaciones no relacionadas con PQR.

```
Agente 'ia_pqr'
├── PqrKnowledgeBaseSearchTool  (knowledge_base_search)
├── PqrTool                     (create_response_pqr)
└── PqrStatsTool                (count_pqr, get_pqr_available_filters, get_pqr_filter_options)
```

El agente `ia_pqr` también queda registrado como subagente del orquestador (`ia_orchestrator`) — accesible como tool `pqr_agent` cuando el usuario opera en modo "todos los procesos" (`processId=0`).

El agente se activa automáticamente en dos contextos:

| Contexto | Cómo se activa |
|---|---|
| Chat usuario (`POST /api/pqr/ia/chat`) | `askChatForPqr` implementa `ModuleFormatAwareChat` → `getModuleFormatName() = 'pqr'` |
| Chat admin con proceso PQR seleccionado | `IAProcess.mainFormat.nombre = 'pqr'` → `ModuleAgentRegistry` resuelve `PqrAgentProvider` |

`PqrAgentProvider` implementa `ModuleAgentProviderInterface` del bundle `ia` y expone el agente `ia_pqr` al sistema de resolución.

## Chat IA

Endpoint: `POST /api/pqr/ia/chat`

Permite a funcionarios hacer preguntas en lenguaje natural sobre PQRs y generar respuestas oficiales directamente desde el chat.

```json
{
  "message": "Redacta una respuesta a esta PQR",
  "sessionId": "uuid-opcional",
  "documentId": 1234,
  "processId": 5
}
```

Requiere que el proceso IA esté habilitado para el formato PQR (`PqrIaGuard`).

## Herramientas del agente `ia_pqr`

**`PqrKnowledgeBaseSearchTool`** (`knowledge_base_search`) — wrapper de `KnowledgeBaseSearchTool` pre-configurado para PQR. Inyecta automáticamente el `processId` del proceso PQR en cada búsqueda. Si PQR no está configurado como proceso IA (`PqrIaGuard::getPqrProcessId() = null`), retorna error en lugar de buscar sin filtro (evita exponer documentos de otros procesos).

**`PqrTool`** (`create_response_pqr`) — registra una respuesta oficial a una PQR. Recibe `documentId` (ID raíz de la PQR), `subject` y `contentAnswers`. Crea el documento de respuesta con los datos del funcionario activo, tipo de distribución y despedida por defecto.

**`PqrStatsTool`** — estadísticas en tiempo real sobre la vista `vpqr`:

| Tool | Descripción |
|---|---|
| `get_pqr_available_filters` | Lista los campos disponibles para filtrar |
| `get_pqr_filter_options` | Opciones de `sys_dependencia` y `sys_tipo` por nombre |
| `count_pqr` | Cuenta PQRs con filtros y/o agrupación (`fecha`, `canal_recepcion`, `sys_estado`, `sys_tipo`, `sys_dependencia`, etc.) |

Todas las herramientas verifican `PqrIaGuard::isPqrEnabled()` antes de ejecutarse.

## `PqrIaGuard` — fuente de verdad del módulo

Centraliza la verificación de si el módulo PQR está configurado como proceso IA:

- `isPqrEnabled(): bool` — si existe al menos un `ia_process` vinculado al formato `pqr`.
- `getPqrProcessId(): ?int` — retorna el ID del proceso o `null` si no está configurado.
- `FORMAT_NAME = 'pqr'` — constante pública usada por `PqrAgentProvider`, `PqrFormatoJsonProcessor`, `PqrDocumentProcessor`, `PqrStatsTool` y `PqrIaGuard` mismo.

## Servidor MCP

**`PqrMcpFormatProvider`** implementa `McpFormatProviderInterface` y registra el formato PQR en el servidor MCP. Permite que agentes externos (Claude Desktop, Cursor, AWS AgentCore) radiquen PQRs autónomamente:

```
list_formats          → identifica idformato del PQR
get_format_structure  → conoce los campos requeridos
search_field_options  → resuelve lookups (ciudad, dependencia, localidad)
create_document       → radica la PQR
```

## Procesador de formato (`PqrFormatoJsonProcessor`)

Personaliza el JSON de estructura del formato PQR entregado al agente (`FormatoJsonProcessorInterface`):

- **Excluye** campos internos: `destino_interno`, `colilla`, `select_mensajeria`.
- **Agrega** campos `TYPE_DEPENDENCIA` y `TYPE_LOCALIDAD` como campos de lookup.
- Metadatos de lookup **cacheados 1 semana** (`pqr_ia_lookup_fields`).

Para invalidar el caché tras modificar campos del formulario:

```bash
php bin/console cache:pool:delete cache.app pqr_ia_lookup_fields
```

## Serialización para Knowledge Base

`PqrJsonForIA` y `PqrRespuestaDataJsonForIA` convierten documentos PQR y sus respuestas a JSON/Markdown para indexarlos en AWS Bedrock Knowledge Base mediante `app:ia:full-sync`.
