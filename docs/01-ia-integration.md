# PQR — Integración con el bundle IA

La integración IA del módulo PQR se carga condicionalmente: solo si el bundle `ia` está instalado.
Los archivos de configuración condicional están en `Resources/config/ia_routes.php` e `ia_services.php`.

---

## Agente dedicado `ia_pqr`

El módulo PQR tiene su **propio agente IA** (`ia_pqr`) con herramientas exclusivas. Estas herramientas
**no están disponibles** en el agente genérico `ia_orchestrator`, evitando que aparezcan en conversaciones
no relacionadas con PQR.

```
Agente 'ia_pqr'
├── PqrKnowledgeBaseSearchTool  (knowledge_base_search — wrapper con processId PQR auto-inyectado)
├── PqrTool                     (create_response_pqr)
└── PqrStatsTool                (count_pqr, get_pqr_available_filters, get_pqr_filter_options)
```

El agente se activa automáticamente en dos contextos:

| Contexto | Cómo se activa |
|----------|----------------|
| Chat usuario (`POST /api/pqr/ia/chat`) | `askChatForPqr` implementa `ModuleFormatAwareChat` → `getModuleFormatName() = 'pqr'` |
| Chat admin con proceso PQR seleccionado | `IAProcess.mainFormat.nombre = 'pqr'` → `ModuleAgentRegistry` resuelve `PqrAgentProvider` |

---

## Herramientas del agente `ia_pqr`

### `PqrKnowledgeBaseSearchTool` (`knowledge_base_search`)

Wrapper de `KnowledgeBaseSearchTool` pre-configurado para PQR. Inyecta automáticamente el `processId`
del proceso PQR en cada búsqueda. Si PQR no está configurado como proceso IA, retorna error en lugar
de buscar sin filtro (evita exponer documentos de otros procesos).

### `PqrTool` (`create_response_pqr`)

Registra una respuesta oficial a una PQR. Recibe:
- `documentId` — ID raíz de la PQR
- `subject` — Asunto de la respuesta
- `contentAnswers` — Contenido de la respuesta

Crea el documento de respuesta con los datos del funcionario activo, tipo de distribución y despedida
por defecto. La guía de flujo completo está en el atributo `#[AsTool]` de la clase.

### `PqrStatsTool`

Estadísticas en tiempo real sobre la vista `vpqr`:

| Tool | Descripción |
|------|-------------|
| `get_pqr_available_filters` | Lista los campos disponibles para filtrar |
| `get_pqr_filter_options` | Opciones de `sys_dependencia` y `sys_tipo` por nombre |
| `count_pqr` | Cuenta PQRs con filtros y/o agrupación por fecha, canal, estado, etc. |

---

## `PqrIaGuard` — fuente de verdad del módulo

Centraliza la verificación de si el módulo PQR está configurado como proceso IA:

- `isPqrEnabled(): bool` — si existe al menos un `ia_process` vinculado al formato `pqr`
- `getPqrProcessId(): ?int` — ID del proceso o `null` si no está configurado
- `FORMAT_NAME = 'pqr'` — constante pública usada en todo el módulo para evitar literales dispersos

---

## Servidor MCP y consulta de radicados WSO

`PqrMcpFormatProvider` implementa `McpFormatProviderInterface` y cubre:

1. **Registro en `list_formats`** — el formato PQR aparece en la lista de formatos disponibles
2. **Radicación por agentes MCP** — `create_document` radica PQRs autónomamente
3. **Consulta de radicados por WhatsApp** — `resolve()` busca el radicado y retorna `FilingStatusDto`

La consulta de radicados valida el email del usuario contra `documento.descripcion`. El email es
opcional — el usuario puede escribir "omitir" y consultar solo por número de consecutivo.

Si el radicado existe, retorna una URL infoQR que el orquestador WSO muestra como botón CTA
"Ver más información" en WhatsApp (no como texto crudo).

Ver `src/Bundles/pqr/IA/Mcp/PqrMcpFormatProvider.php` para la implementación completa.

---

## `PqrFormatoJsonProcessor`

Personaliza el JSON de estructura del formato PQR entregado al agente (`FormatoJsonProcessorInterface`):

- **Excluye** campos internos: `destino_interno`, `colilla`, `select_mensajeria`
- **Agrega** campos `TYPE_DEPENDENCIA` y `TYPE_LOCALIDAD` como campos de lookup
- Metadatos de lookup **cacheados 1 semana** (`pqr_ia_lookup_fields`)

Para invalidar el caché tras modificar campos del formulario:

```bash
php bin/console cache:pool:delete cache.app pqr_ia_lookup_fields
```

---

## `PqrDocumentProcessor`

Implementa `FormatoDocumentProcessorInterface` para el formato PQR. Genera el PDF del radicado y
retorna un `DocumentCreatedDto` con:

- `documentId` — ID del documento creado
- `radicado` — número de radicado
- `consecutivo` — número de consecutivo
- `pdfUrl` — URL pública del PDF (TTL 24h)

---

## Serialización para Knowledge Base

`PqrJsonForIA` y `PqrRespuestaDataJsonForIA` convierten documentos PQR y sus respuestas a JSON/Markdown
para indexarlos en AWS Bedrock Knowledge Base mediante `bin/console app:ia:full-sync`.

---

## Variable de entorno

| Variable | Default | Descripción |
|----------|---------|-------------|
| `IA_PQR_MODEL` | `claude-haiku-4-5-20251001` | Modelo del agente `ia_pqr`. Cambiar a Sonnet si se requiere mayor calidad en redacción. |
