# Estructura de directorios

Estructura post-refactor ORM (rama `refactor/pqr-orm`).

```
src/Bundles/pqr/
├── Command/
│   ├── GenerateIndixesPqrCommand.php         # app:generate:indexesPqr
│   └── GenerateJsonTranslatorForPQRCommand.php # app:generate:json-translator-for-pqr
│
├── Controller/
│   ├── CaptchaController.php                 # POST /api/pqr/captcha/saveDocument
│   ├── ComponentsController.php              # Autocomplete y búsqueda dinámica
│   ├── FtPqrController.php                   # Gestión principal del formato PQR
│   ├── FtPqrRespuestaController.php          # Gestión de respuestas
│   ├── PqrBalancerController.php             # Balanceador de carga entre funcionarios
│   ├── PqrController.php                     # Búsqueda y utilidades generales
│   ├── PqrFormController.php                 # Configuración del formulario
│   ├── PqrFormFieldController.php            # CRUD de campos del formulario
│   ├── PqrNotificationController.php         # Gestión de notificaciones por email
│   ├── PqrResponseTimeController.php         # Tiempos de respuesta por tipo
│   ├── StructureController.php               # Tipos y estructura de PQR
│   └── WebserviceController.php              # Webservice público de radicación
│
├── Entity/                                   # Entidades Doctrine ORM (tablas pqr_*)
│   ├── PqrForm.php                           # pqr_forms — configuración del formulario
│   ├── PqrFormField.php                      # pqr_form_fields — campos del formulario
│   ├── PqrHtmlField.php                      # pqr_html_fields — catálogo de tipos de campo
│   ├── PqrHistory.php                        # pqr_history — historial de cambios
│   ├── PqrNotification.php                   # pqr_notifications — notificaciones email
│   ├── PqrNotyMessage.php                    # pqr_noty_messages — mensajes de notificación
│   ├── PqrResponseTime.php                   # pqr_response_times — tiempos de respuesta
│   ├── PqrBalancer.php                       # pqr_balancer — grupos del balanceador
│   └── PqrBackup.php                         # pqr_backups — respaldos JSON
│
├── Event/
│   ├── PqrFormFieldCreatedEvent.php
│   ├── PqrFormFieldEvent.php                 # Clase base
│   ├── PqrFormFieldDeleteEvent.php
│   └── PqrFormFieldUpdateEvent.php
│
├── EventSubscriber/
│   └── PqrSubscriber.php                     # Escucha tareas y emails relacionados con PQRs
│
├── IA/                                       # Integración con bundle ia (ver ia-integration.md)
│   ├── Controller/
│   │   └── PqrChatController.php             # POST /api/pqr/ia/chat
│   ├── Dto/
│   │   └── askChatForPqr.php                 # DTO de request de chat
│   ├── Mcp/
│   │   └── PqrMcpFormatProvider.php          # Registra formato PQR en servidor MCP
│   └── Service/
│       ├── PqrAgentProvider.php              # Expone ia_pqr al ModuleAgentRegistry
│       ├── PqrCustomParameterForIA.php       # Parámetros de contexto para el modelo IA
│       ├── PqrDocumentProcessor.php          # Procesador de documentos PQR
│       ├── PqrFormatoJsonProcessor.php       # Personaliza JSON de estructura del formato
│       ├── PqrIaGuard.php                    # Verifica si IA está habilitada; FORMAT_NAME='pqr'
│       ├── PqrJsonForIA.php                  # Serializa PQR a JSON para knowledge base
│       ├── PqrRespuestaDataJsonForIA.php     # Serializa respuestas para knowledge base
│       └── Tools/
│           ├── PqrKnowledgeBaseSearchTool.php # Wrapper KnowledgeBaseSearchTool para PQR
│           ├── PqrStatsTool.php              # Tool IA: estadísticas y conteos
│           └── PqrTool.php                   # Tool IA: crear respuestas oficiales
│
├── Repository/                               # Repositorios Doctrine ORM
│   ├── PqrFormRepository.php                 # findActiveOrFail(), findActive()
│   ├── PqrFormFieldRepository.php            # findByName(), findByPqrFormOrdered(), getReportFieldsData()
│   ├── PqrHtmlFieldRepository.php
│   ├── PqrHistoryRepository.php
│   ├── PqrNotificationRepository.php         # findByPqrForm()
│   ├── PqrNotyMessageRepository.php          # findByNotification()
│   ├── PqrResponseTimeRepository.php         # findByField()
│   ├── PqrBalancerRepository.php             # findByField()
│   └── PqrBackupRepository.php
│
├── Resources/
│   ├── config/
│   │   ├── routes.yaml                       # Rutas del bundle (/api/pqr)
│   │   ├── services.yaml                     # Servicios Symfony
│   │   ├── ia_routes.php                     # Rutas IA (carga condicional)
│   │   ├── ia_services.php                   # Servicios IA (carga condicional)
│   │   ├── migrations.yaml                   # Path de migraciones Doctrine
│   │   └── translation.yaml                  # Path de traducciones
│   └── migrations/
│       ├── TMigrations.php                   # Trait base para migraciones
│       ├── TDependencyReport.php             # Trait para reportes de dependencias
│       └── Version*.php                      # 12 migraciones (2019 → 2025)
│
├── Service/                                  # Servicios Symfony DI (ORM puro)
│   ├── PqrFormProvider.php                   # ORM singleton: findActiveOrFail() — reemplaza PqrForm::getInstance()
│   └── PqrFormFieldServiceFactory.php        # Factory DI para PqrFormFieldService
│
├── Services/                                 # Servicios legacy + business logic
│   ├── FtPqrService.php                      # Ciclo de vida PQR (extiende ModelService)
│   ├── FtPqrRespuestaService.php             # Gestión de respuestas a ciudadanos
│   ├── PqrService.php                        # Utilidades generales
│   ├── PqrFormService.php                    # Configuración del formulario
│   ├── PqrFormFieldService.php               # CRUD de campos
│   ├── PqrHistoryService.php                 # Historial de cambios
│   ├── PqrNotificationService.php            # Notificaciones por email
│   ├── PqrNotyMessageService.php             # Mensajes de notificación
│   ├── Customizable/
│   │   └── PqrCustomizable.php               # Clase extensible por cliente
│   ├── crontab/
│   │   └── ChangeStatusOfOportunoField.php   # Actualiza estado oportuno/extemporáneo de PQRs
│   ├── generadoresWs/                        # Generadores de archivos del webservice
│   │   ├── GenerateWsPqr.php
│   │   └── GenerateWsPqrCalificacion.php
│   └── controllers/
│       ├── WebservicePqr.php                 # Generador del webservice de radicación
│       ├── WebserviceCalificacion.php        # Generador del webservice de calificación
│       ├── AddEditFormat/                    # Renderizadores del formulario PQR
│       │   ├── AddEditFtPqr.php
│       │   ├── IAddEditFormat.php
│       │   └── fields/                       # Tipos de campo: Text, Select, Date, etc.
│       ├── customFields/                     # Campos especiales
│       │   ├── Autocomplete.php
│       │   ├── Dependencia.php
│       │   ├── Localidad.php
│       │   └── Tratamiento.php
│       └── templates/                        # Templates HTML/JS del formulario público
│
├── formatos/
│   ├── pqr/
│   │   ├── FtPqrProperties.php               # Definición de campos en BD
│   │   ├── FtPqr.php                         # Hooks del ciclo de vida (extiende ModelFormat)
│   │   ├── functionsReport.php               # Funciones de reporte (auto-generado)
│   │   └── reporteFunciones.php              # Funciones auxiliares del reporte
│   ├── pqr_respuesta/
│   │   ├── FtPqrRespuestaProperties.php
│   │   └── FtPqrRespuesta.php
│   └── pqr_calificacion/
│       ├── FtPqrCalificacionProperties.php
│       └── FtPqrCalificacion.php
│
├── helpers/
│   └── UtilitiesPqr.php                      # Utilidades generales del módulo
│
├── docs/                                     # Documentación del módulo (este directorio)
│
└── translations/                             # Archivos i18n del módulo
```

## Notas de acceso a datos

| Directorio | Patrón | Tablas |
|---|---|---|
| `Entity/` + `Repository/` | Doctrine ORM | `pqr_*` |
| `formatos/` | Active Record legacy (`ModelFormat`) | `ft_pqr`, `ft_pqr_respuesta`, `ft_pqr_calificacion` |
| `Service/PqrFormProvider` | Symfony DI + ORM | `pqr_forms` |

Las clases en `formatos/` no pueden migrar a ORM porque extienden `ModelFormat` del núcleo SAIA (fuera del módulo). Para acceder a entidades ORM desde estas clases se usa `LegacyServiceLocator::getInstance()->getEntityManager()`.
