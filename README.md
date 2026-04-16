# SAIA — Módulo PQR (`src/Bundles/pqr`)

Módulo para la gestión integral de Peticiones, Quejas y Reclamos (PQR) dentro del sistema SAIA. Permite configurar
formularios dinámicos, publicarlos como webservice público, gestionar el ciclo de vida de cada PQR y responderla con
trazabilidad completa.

---

## Índice

1. [¿Qué hace este módulo?](#1-qué-hace-este-módulo)
2. [Arquitectura general](#2-arquitectura-general)
3. [Estructura de directorios](#3-estructura-de-directorios)
4. [Instalación](#4-instalación)
5. [Base de datos](#5-base-de-datos)
6. [Endpoints REST](#6-endpoints-rest)
7. [Comandos de consola](#7-comandos-de-consola)
8. [Flujos de trabajo](#8-flujos-de-trabajo)
9. [Características especiales](#9-características-especiales)
10. [Eventos](#10-eventos)
11. [Integración IA](#11-integración-ia)
12. [Frontend](#12-frontend)
13. [Webservice público](#13-webservice-público)
14. [Personalización](#14-personalización)

---

## 1. ¿Qué hace este módulo?

### 1.1 Gestión del ciclo de vida de PQRs

Permite radicar, clasificar, asignar y responder PQRs con historial completo de cambios. Cada PQR pasa por los estados *
*PENDIENTE → INICIADO → PROCESO → TERMINADO** con tiempos de respuesta configurables.

### 1.2 Formulario dinámico configurable

El administrador diseña el formulario desde la interfaz SAIA: agrega campos de distintos tipos (texto, select, fecha,
archivo, autocomplete, dependencia, localidad, etc.), los ordena y lo publica. El sistema genera automáticamente el
webservice público y los archivos de frontend necesarios.

### 1.3 Webservice público

Una vez publicado, el formulario queda disponible en `https://DOMINIO/ws/pqr`. Los ciudadanos ingresan, completan el
formulario y su PQR queda radicada en SAIA sin necesidad de autenticación.

### 1.4 Respuestas y calificaciones

El funcionario responde la PQR, el sistema envía notificación al ciudadano con la respuesta y puede solicitar una
encuesta de satisfacción. La calificación queda asociada al expediente.

### 1.5 Integración IA

Chat conversacional sobre PQRs, herramientas de estadísticas para el agente IA, y exposición del formato PQR como tool
del servidor MCP para radicación autónoma por agentes.

---

## 2. Arquitectura general

```
┌─────────────────────────────────────────────────────────────┐
│                      Clientes                               │
│  Navegador (admin)   Ciudadano (ws/pqr)   Agente MCP / IA  │
└────────┬──────────────────┬──────────────────┬──────────────┘
         │  /api/pqr/*       │  /ws/pqr          │  MCP / chat
         ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────┐
│                  Bundle PQR — Symfony 7.4                   │
│                                                             │
│  FtPqrController        WebserviceController   PqrChatCtrl │
│  FtPqrRespuestaCtrl     CaptchaController                   │
│       │                      │                    │         │
│  FtPqrService          PqrFormService       PqrStatsTool   │
│  FtPqrRespuestaService  PqrFormFieldService  PqrTool       │
│  PqrHistoryService      PqrNotificationService              │
│  PqrBalancerService     PqrResponseTimeService              │
└──────────────────────────┬──────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              │                         │
     ┌────────▼────────┐      ┌─────────▼────────┐
     │  Base de datos   │      │   Bundle IA       │
     │  - ft_pqr        │      │  - ChatService    │
     │  - pqr_forms     │      │  - McpToolProvider│
     │  - pqr_history   │      │  - Knowledge Base │
     └──────────────────┘      └──────────────────┘
```

---

## 3. Estructura de directorios

```
src/Bundles/pqr/
├── Command/
│   ├── GenerateIndixesPqrCommand.php       # app:generate:indexesPqr
│   ├── GenerateJsonTranslatorForPQRCommand.php # app:generate:jsonTranslatorForPQR
│   └── ImportFromFileCommand.php           # app:import:pqrFromFile
│
├── Controller/
│   ├── CaptchaController.php               # POST /api/pqr/captcha/saveDocument
│   ├── ComponentsController.php            # Autocomplete y búsqueda dinámica
│   ├── FtPqrController.php                 # Gestión principal del formato PQR
│   ├── FtPqrRespuestaController.php        # Gestión de respuestas
│   ├── PqrBalancerController.php           # Balanceador de carga entre funcionarios
│   ├── PqrController.php                   # Búsqueda y utilidades generales
│   ├── PqrFormController.php               # Configuración del formulario
│   ├── PqrFormFieldController.php          # CRUD de campos del formulario
│   ├── PqrNotificationController.php       # Gestión de notificaciones por email
│   ├── PqrResponseTimeController.php       # Tiempos de respuesta por tipo
│   ├── StructureController.php             # Tipos y estructura de PQR
│   └── WebserviceController.php            # Webservice público de radicación
│
├── Event/
│   ├── PqrFormFieldCreatedEvent.php
│   ├── PqrFormFieldEvent.php               # Clase base
│   ├── PqrFormFieldDeleteEvent.php
│   └── PqrFormFieldUpdateEvent.php
│
├── EventSubscriber/
│   └── PqrSubscriber.php                   # Escucha tareas y emails relacionados con PQRs
│
├── IA/                                     # Integración con bundle ia (ver sección 11)
│   ├── Controller/
│   │   └── PqrChatController.php           # POST /api/pqr/ia/chat
│   ├── Dto/
│   │   └── askChatForPqr.php               # DTO de request de chat
│   ├── Mcp/
│   │   └── PqrMcpFormatProvider.php        # Registra formato PQR en servidor MCP
│   └── Service/
│       ├── PqrAgentProvider.php            # Expone ia_pqr al ModuleAgentRegistry
│       ├── PqrCustomParameterForIA.php     # Parámetros de contexto para el modelo IA
│       ├── PqrDocumentProcessor.php        # Procesador de documentos PQR
│       ├── PqrFormatoJsonProcessor.php     # Personaliza JSON de estructura del formato
│       ├── PqrIaGuard.php                  # Verifica si IA está habilitada; FORMAT_NAME='pqr'
│       ├── PqrJsonForIA.php                # Serializa PQR a JSON para knowledge base
│       ├── PqrRespuestaDataJsonForIA.php   # Serializa respuestas para knowledge base
│       └── Tools/
│           ├── PqrKnowledgeBaseSearchTool.php # Wrapper de KnowledgeBaseSearchTool pre-configurado para PQR
│           ├── PqrStatsTool.php            # Tool IA: estadísticas y conteos
│           └── PqrTool.php                 # Tool IA: crear respuestas oficiales
│
├── Resources/
│   ├── config/
│   │   ├── routes.yaml                     # Rutas del bundle (/api/pqr)
│   │   ├── services.yaml                   # Servicios Symfony
│   │   ├── ia_routes.php                   # Rutas IA (carga condicional)
│   │   ├── ia_services.php                 # Servicios IA (carga condicional)
│   │   ├── migrations.yaml                 # Path de migraciones Doctrine
│   │   └── translation.yaml               # Path de traducciones
│   └── migrations/
│       ├── TMigrations.php                 # Trait base para migraciones
│       ├── TDependencyReport.php           # Trait para reportes de dependencias
│       └── Version*.php                    # 12 migraciones (2019 → 2025)
│
├── Services/
│   ├── FtPqrService.php                    # Servicio principal: ciclo de vida PQR
│   ├── FtPqrRespuestaService.php           # Gestión de respuestas a ciudadanos
│   ├── PqrService.php                      # Utilidades generales
│   ├── PqrFormService.php                  # Configuración del formulario
│   ├── PqrFormFieldService.php             # CRUD de campos
│   ├── PqrHistoryService.php               # Historial de cambios
│   ├── PqrNotificationService.php          # Notificaciones por email
│   ├── PqrNotyMessageService.php           # Mensajes de notificación
│   ├── PqrResponseTimeService.php          # Tiempos de respuesta por campo
│   ├── PqrBalancerService.php              # Balanceador de cargas
│   ├── PqrBackupService.php                # Respaldos de datos
│   ├── Customizable/
│   │   └── PqrCustomizable.php             # Clase extensible para personalizaciones
│   ├── controllers/
│   │   ├── WebservicePqr.php               # Generador del webservice de radicación
│   │   ├── WebserviceCalificacion.php      # Generador del webservice de calificación
│   │   ├── AddEditFormat/                  # Renderizadores del formulario PQR
│   │   │   ├── AddEditFtPqr.php
│   │   │   ├── IAddEditFormat.php
│   │   │   └── fields/                     # Tipos de campo: Text, Select, Date, etc.
│   │   ├── customFields/                   # Campos especiales
│   │   │   ├── Autocomplete.php
│   │   │   ├── Dependencia.php
│   │   │   ├── Localidad.php
│   │   │   └── Tratamiento.php
│   │   ├── generadoresWs/                  # Generadores de archivos del webservice
│   │   └── templates/                      # Templates HTML/JS del formulario público
│   └── models/
│       ├── PqrForm.php                     # Singleton: configuración del formulario
│       ├── PqrFormField.php                # Campos del formulario
│       ├── PqrHtmlField.php                # Tipos de campo HTML disponibles
│       ├── PqrHistory.php                  # Historial
│       ├── PqrNotification.php             # Notificaciones
│       ├── PqrResponseTime.php             # Tiempos de respuesta
│       ├── PqrBalancer.php                 # Balanceador
│       ├── PqrBackup.php                   # Respaldos
│       └── PqrNotyMessage.php              # Mensajes de notificación
│
├── formatos/
│   ├── pqr/
│   │   ├── FtPqrProperties.php             # Definición de campos en BD
│   │   └── FtPqr.php                       # Hooks del ciclo de vida
│   ├── pqr_respuesta/
│   │   ├── FtPqrRespuestaProperties.php
│   │   └── FtPqrRespuesta.php
│   └── pqr_calificacion/
│       ├── FtPqrCalificacionProperties.php
│       └── FtPqrCalificacion.php
│
├── helpers/
│   └── UtilitiesPqr.php                    # Utilidades generales del módulo
│
└── translations/                           # Archivos i18n del módulo
```

---

## 4. Instalación

El módulo está incluido en el repositorio principal de SAIA. Solo se requiere ejecutar las migraciones:

```bash
php bin/console doctrine:migrations:migrate
```

Para regenerar los índices de base de datos:

```bash
php bin/console app:generate:indexesPqr
```

Para compilar el frontend:

```bash
cd public/views/modules/pqr
npm install
npm run build
```

---

## 5. Base de datos

### Tablas principales

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

### Estados de `ft_pqr`

| Constante          | Valor         | Descripción                    |
|--------------------|---------------|--------------------------------|
| `ESTADO_PENDIENTE` | `'PENDIENTE'` | Radicada, sin asignar          |
| `ESTADO_INICIADO`  | `'INICIADO'`  | Asignada, sin trabajo iniciado |
| `ESTADO_PROCESO`   | `'PROCESO'`   | En gestión activa              |
| `ESTADO_TERMINADO` | `'TERMINADO'` | Respondida y cerrada           |

### Oportunidad de respuesta

| Constante                            | Descripción               |
|--------------------------------------|---------------------------|
| `OPORTUNO_PENDIENTES_SIN_VENCER`     | Abiertas dentro del plazo |
| `OPORTUNO_VENCIDAS_SIN_CERRAR`       | Abiertas y fuera de plazo |
| `OPORTUNO_CERRADAS_A_TERMINO`        | Cerradas dentro del plazo |
| `OPORTUNO_CERRADAS_FUERA_DE_TERMINO` | Cerradas fuera del plazo  |

### Niveles de severidad / impacto / frecuencia

| Constante                  | Valor |
|----------------------------|-------|
| `ESTADO_FRE_IMP_SEV_BAJO`  | `1`   |
| `ESTADO_FRE_IMP_SEV_MEDIO` | `2`   |
| `ESTADO_FRE_IMP_SEV_ALTO`  | `3`   |

### Tipos de historial (`pqr_history.tipo`)

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

---

## 6. Endpoints REST

Prefijo: `/api/pqr`. Requieren autenticación SAIA (firewall `main`).

### Formato PQR

| Método | Ruta                                 | Descripción                                  |
|--------|--------------------------------------|----------------------------------------------|
| `GET`  | `/api/pqr/{idft}/history`            | Historial de cambios de la PQR               |
| `GET`  | `/api/pqr/{idft}/externalUser`       | Datos del ciudadano                          |
| `POST` | `/api/pqr/{idft}/externalUser`       | Actualiza datos del ciudadano                |
| `GET`  | `/api/pqr/{idft}/dataToLoadResponse` | Datos para cargar el formulario de respuesta |
| `GET`  | `/api/pqr/{idft}/dateForType`        | Fecha de vencimiento por tipo                |
| `GET`  | `/api/pqr/{idft}/valuesForType`      | Valores disponibles por tipo                 |
| `PUT`  | `/api/pqr/{idft}/updateType`         | Cambia el tipo de PQR                        |
| `PUT`  | `/api/pqr/{idft}/finish`             | Finaliza la PQR                              |

### Respuestas

| Método | Ruta                                           | Descripción                                 |
|--------|------------------------------------------------|---------------------------------------------|
| `GET`  | `/api/pqr/answers/{idft}/requestSurveyByEmail` | Envía encuesta de satisfacción al ciudadano |

### Configuración del formulario

| Método | Ruta                                  | Descripción                               |
|--------|---------------------------------------|-------------------------------------------|
| `GET`  | `/api/pqr/form/setting`               | Configuración actual                      |
| `GET`  | `/api/pqr/form/textFields`            | Campos de tipo texto disponibles          |
| `GET`  | `/api/pqr/form/responseSetting`       | Config de respuesta                       |
| `PUT`  | `/api/pqr/form/publish`               | Publica el formulario (genera webservice) |
| `PUT`  | `/api/pqr/form/sortFields`            | Reordena los campos                       |
| `PUT`  | `/api/pqr/form/updateSetting`         | Actualiza configuración general           |
| `PUT`  | `/api/pqr/form/updateResponseSetting` | Actualiza configuración de respuesta      |
| `PUT`  | `/api/pqr/form/balancer`              | Activa/desactiva balanceador              |
| `PUT`  | `/api/pqr/form/filterReport`          | Filtro de reporte por dependencia         |
| `PUT`  | `/api/pqr/form/receivingchannels`     | Canales de recepción habilitados          |

### Campos del formulario

| Método   | Ruta                               | Descripción        |
|----------|------------------------------------|--------------------|
| `POST`   | `/api/pqr/formField`               | Crea un campo      |
| `PUT`    | `/api/pqr/formField/{id}`          | Actualiza un campo |
| `PUT`    | `/api/pqr/formField/{id}/active`   | Activa el campo    |
| `PUT`    | `/api/pqr/formField/{id}/inactive` | Desactiva el campo |
| `DELETE` | `/api/pqr/formField/{id}`          | Elimina el campo   |

### Notificaciones

| Método   | Ruta                         | Descripción                       |
|----------|------------------------------|-----------------------------------|
| `POST`   | `/api/pqr/notification`      | Crea notificación por email       |
| `PUT`    | `/api/pqr/notification/{id}` | Actualiza notificación            |
| `DELETE` | `/api/pqr/notification/{id}` | Elimina notificación              |
| `PUT`    | `/api/pqr/notyMessage/{id}`  | Actualiza mensaje de notificación |

### Tiempos de respuesta y balanceador

| Método | Ruta                                | Descripción                          |
|--------|-------------------------------------|--------------------------------------|
| `GET`  | `/api/pqr/responseTimes/field/{id}` | Tiempos configurados para un campo   |
| `PUT`  | `/api/pqr/responseTimes`            | Actualiza tiempos de respuesta       |
| `GET`  | `/api/pqr/balancer/field/{id}`      | Grupos del balanceador para un campo |
| `PUT`  | `/api/pqr/balancer`                 | Actualiza grupos del balanceador     |

### Utilidades

| Método | Ruta                                    | Descripción                        |
|--------|-----------------------------------------|------------------------------------|
| `GET`  | `/api/pqr/searchByNumber`               | Busca PQR por número de radicado   |
| `GET`  | `/api/pqr/historyForTimeline`           | Historial formateado para timeline |
| `GET`  | `/api/pqr/decrypt`                      | Desencripta datos del ciudadano    |
| `GET`  | `/api/pqr/contentDependencia`           | Contenido de una dependencia       |
| `GET`  | `/api/pqr/components/autocomplete/list` | Opciones autocomplete              |
| `GET`  | `/api/pqr/components/autocomplete/find` | Búsqueda autocomplete              |
| `GET`  | `/api/pqr/structure/dataViewIndex`      | Estructura y tipos de PQR          |

### Webservice público (sin autenticación)

| Método | Ruta                               | Descripción                            |
|--------|------------------------------------|----------------------------------------|
| `POST` | `/api/pqr/webservice/saveDocument` | Radica PQR desde el formulario público |
| `POST` | `/api/pqr/captcha/saveDocument`    | Radica PQR con validación CAPTCHA      |

---

## 7. Comandos de consola

```bash
# Genera índices en BD para las tablas del módulo (ft_pqr, pqr_backups, etc.)
php bin/console app:generate:indexesPqr

# Genera archivos JSON de traducción para el módulo
php bin/console app:generate:jsonTranslatorForPQR
```

### Importación masiva de PQRs

```bash
php bin/console app:pqr:import-from-file <archivo>
```

Importa PQRs en bloque desde un archivo `.xlsx`, `.ods` o `.csv`. Crea un documento `ft_pqr` por cada fila.

| Opción          | Descripción                          | Default |
|-----------------|--------------------------------------|---------|
| `file`          | Ruta al archivo (requerido)          | —       |
| `--delimiter`   | Separador de columnas (solo CSV)     | `,`     |
| `--skip-rows N` | Filas iniciales a omitir (cabeceras) | `0`     |
| `--preview N`   | Muestra N registros sin importar     | —       |

Ejemplos:

```bash
# Vista previa de los primeros 5 registros
php bin/console app:pqr:import-from-file archivo.xlsx --preview 5

# Importar CSV con punto y coma, omitiendo la primera fila de cabecera
php bin/console app:pqr:import-from-file datos.csv --delimiter ";" --skip-rows 1
```

El comando realiza GC cada 10 registros y genera logs detallados. Las ciudades se resuelven por caché para optimizar
rendimiento.

---

## 8. Flujos de trabajo

### 8.1 Administrador: configurar y publicar el formulario

1. Accede a `/dashboard/pqr/configuracion`.
2. **Crea los campos** del formulario (`POST /api/pqr/formField`):
    - Selecciona el tipo: `Text`, `Textarea`, `Date`, `Select`, `File`, `Autocomplete`, `Dependencia`, `Localidad`, etc.
    - Configura etiqueta, si es obligatorio, si es visible en modo anónimo y el orden.
3. **Reordena** los campos arrastrando (`PUT /api/pqr/form/sortFields`).
4. **Configura notificaciones** (`POST /api/pqr/notification`): qué funcionarios reciben email ante cada evento.
5. **Define tiempos de respuesta** por tipo de PQR (`PUT /api/pqr/responseTimes`). Ej.: "Petición → 10 días hábiles", "
   Queja → 15 días".
6. **Activa el balanceador** si se quiere distribución automática (`PUT /api/pqr/form/balancer`): asigna la PQR al grupo
   de funcionarios menos cargado para ese tipo.
7. **Publica el formulario** (`PUT /api/pqr/form/publish`): el sistema genera los archivos del webservice y deja
   disponible `https://DOMINIO/ws/pqr`.

### 8.2 Ciudadano: radicar una PQR

1. Accede a `https://DOMINIO/ws/pqr` (sin autenticación).
2. Completa el formulario con los campos configurados por el administrador.
3. Si el formulario lo permite, puede marcar **Anónimo**: los campos de identificación se ocultan o se vuelven
   opcionales según la configuración de cada campo.
4. Adjunta archivos si aplica.
5. Resuelve el CAPTCHA (si está habilitado).
6. Envía el formulario (`POST /api/pqr/captcha/saveDocument` o `POST /api/pqr/webservice/saveDocument`).
7. El sistema retorna el **número de radicado**. Con ese número, el ciudadano puede consultar el estado en
   `https://DOMINIO/ws/pqr/consulta`.

**Lo que ocurre al radicar:**

- Se crea el registro en `ft_pqr` con estado `PENDIENTE`.
- Se genera un respaldo JSON en `pqr_backups`.
- Se calcula `sys_fecha_vencimiento` según el tipo de PQR y `pqr_response_times`.
- El balanceador asigna el documento si está activo.
- Se notifica por email a los funcionarios configurados en `pqr_notifications`.

### 8.3 Funcionario: gestionar y responder

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

### 8.4 Ciudadano: calificar y consultar

- **Calificación:** Desde el enlace en el email de respuesta (`https://DOMINIO/ws/pqr/calificacion`), el ciudadano
  completa la encuesta. El registro queda en `ft_pqr_calificacion`.
- **Consulta de estado:** Accediendo a `https://DOMINIO/ws/pqr/consulta` e ingresando el número de radicado, el
  ciudadano ve el estado actual y el historial de eventos con timeline.

### 8.5 Flujo técnico resumido (radicación)

```
Ciudadano  →  POST /api/pqr/captcha/saveDocument
                └─ CaptchaController valida CAPTCHA
                └─ SaveDocument (core SAIA) crea ft_pqr
                    └─ FtPqr::afterRad()
                        ├─ createTaskFromDataTemp()   → genera tarea si aplica
                        ├─ postDocumentRad()          → post-radicación
                        └─ sendDocumentsByEmail()     → notifica funcionarios
                └─ FtPqrService::createBackup()       → respaldo JSON
                └─ PqrSubscriber::onTaskCreated()     → historial + estado INICIADO
                └─ Calcula sys_fecha_vencimiento      → pqr_response_times
                └─ Balanceador (si activo)            → asigna a funcionario
         ←  {"number": "PQR-2026-0001234"}
```

---

## 9. Características especiales

### Formulario 100% dinámico

El administrador diseña los campos desde la interfaz sin escribir código. Puede agregar, reordenar, activar/desactivar y
configurar cada campo de forma independiente. Al publicar, el sistema regenera automáticamente el frontend del
webservice.

### Anonimato

Si `PqrForm.show_anonymous = 1`, el formulario público muestra un checkbox "Anónimo". Cada campo tiene un atributo
`anonymous` que indica si debe ser visible o requerido cuando el usuario elige esta opción. Al radicar de forma anónima,
el sistema crea un tercero sin identificación personal.

### Balanceador de carga

Si `enable_balancer = 1`, al radicar una PQR el sistema consulta `pqr_balancer` para el tipo de PQR y selecciona el
grupo con menor carga de trabajo. Esto evita que un solo funcionario reciba todas las PQRs de un mismo tipo.

### Tiempos de respuesta

Configurables por tipo de PQR en `pqr_response_times`. Al radicar, se calcula `sys_fecha_vencimiento` automáticamente.
El campo `enable_con_days` determina si se cuentan **días corridos** (`1`) o **días hábiles** (`0`). Si la PQR no se
responde en el plazo, se marca como `OPORTUNO_VENCIDAS_SIN_CERRAR` y queda visible con prioridad en el dashboard.

### Historial y trazabilidad completa

Cada evento relevante de una PQR queda registrado en `pqr_history` con fecha exacta y el funcionario responsable. Los
tipos de evento van desde creación de tarea hasta modificaciones de datos del ciudadano. Este historial se expone como
un **timeline visual** en la interfaz.

### Respaldos JSON

Cada PQR tiene un respaldo en `pqr_backups` con una copia completa de sus datos en el momento de la radicación. Útil
para auditorías y recuperación de datos.

### Canales de recepción

Configurable por el administrador desde `PUT /api/pqr/form/receivingchannels`. Los canales disponibles son: `WEB`,
`EMAIL`, `FÍSICO`, `TELEFÓNICO`. El canal queda registrado en `ft_pqr.canal_recepcion`.

---

## 10. Eventos

### Eventos del formulario

Disparados por `PqrFormFieldService` al gestionar campos:

| Evento                     | Cuándo se dispara                  |
|----------------------------|------------------------------------|
| `PqrFormFieldCreatedEvent` | Al crear un campo en el formulario |
| `PqrFormFieldUpdateEvent`  | Al actualizar un campo             |
| `PqrFormFieldDeleteEvent`  | Al eliminar un campo               |

### Suscriptor `PqrSubscriber`

Escucha eventos del sistema SAIA y actualiza el estado de la PQR automáticamente:

| Evento escuchado         | Acción                                                                  |
|--------------------------|-------------------------------------------------------------------------|
| `TaskCreatedEvent`       | Registra creación de tarea en historial; avanza estado a `INICIADO`     |
| `TaskDeletedEvent`       | Registra eliminación de tarea en historial                              |
| `TaskStatusCreatedEvent` | Registra cambio de estado de tarea; puede avanzar el estado a `PROCESO` |
| `SentMessageEvent`       | Registra email enviado en historial como `TIPO_NOTIFICACION`            |

---

## 11. Integración IA

La integración IA se carga condicionalmente: solo si el bundle `ia` está instalado. Los archivos de configuración
condicional están en `Resources/config/ia_routes.php` e `ia_services.php`.

### 11.1 Variable de entorno del modelo

| Variable       | Default                     | Descripción                                                                                                                                         |
|----------------|-----------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `IA_PQR_MODEL` | `claude-haiku-4-5-20251001` | Modelo del agente `ia_pqr`. Sonnet por defecto — recomendado para redacción de respuestas oficiales. Configurable en `.env.local` sin tocar código. |

### 11.2 Agente dedicado `ia_pqr`

PQR tiene su **propio agente IA** (`ia_pqr`) con herramientas exclusivas. Estas herramientas **no están disponibles** en
el agente genérico `ia_orchestrator`, evitando que aparezcan en conversaciones no relacionadas con PQR.

```
Agente 'ia_pqr'
├── PqrKnowledgeBaseSearchTool  (knowledge_base_search — wrapper con processId PQR auto-inyectado)
├── PqrTool                     (create_response_pqr)
└── PqrStatsTool                (count_pqr, get_pqr_available_filters, get_pqr_filter_options)
```

El agente `ia_pqr` también queda registrado como subagente del orquestador (`ia_orchestrator`) — accesible como tool
`pqr_agent` cuando el usuario opera en modo "todos los procesos" (`processId=0`).

El agente se activa automáticamente en dos contextos:

| Contexto                                | Cómo se activa                                                                               |
|-----------------------------------------|----------------------------------------------------------------------------------------------|
| Chat usuario (`POST /api/pqr/ia/chat`)  | `askChatForPqr` implementa `ModuleFormatAwareChat` → `getModuleFormatName() = 'pqr'`         |
| Chat admin con proceso PQR seleccionado | `IAProcess.mainFormat.nombre = 'pqr'` → `ModuleAgentRegistry` resuelve `PqrAgentProvider` |

`PqrAgentProvider` implementa `ModuleAgentProviderInterface` del bundle `ia` y expone el agente `ia_pqr` al sistema de
resolución. Solo requiere el agente (`#[Target('ia_pqr')]`) y el parámetro de modelo — las herramientas se registran
directamente en `ia.yaml` y su guía de uso está en los atributos `#[AsTool]` de cada tool.

**Nota sobre admin chat**: cuando el administrador selecciona el proceso PQR, el system prompt se adapta automáticamente:
no incluye `processId` en las instrucciones (el wrapper `PqrKnowledgeBaseSearchTool` lo inyecta internamente). Si
`fullAccess=false`, sí incluye `userId` para restringir la búsqueda a documentos del usuario.

### 11.3 Chat IA

Endpoint: `POST /api/pqr/ia/chat`

Permite a funcionarios hacer preguntas en lenguaje natural sobre PQRs y generar respuestas oficiales directamente desde
el chat.

```json
{
  "message": "Redacta una respuesta a esta PQR",
  "sessionId": "uuid-opcional",
  "documentId": 1234,
  "processId": 5
}
```

Requiere que el proceso IA esté habilitado para el formato PQR (`PqrIaGuard`).

### 11.4 Herramientas del agente `ia_pqr`

**`PqrKnowledgeBaseSearchTool`** (`knowledge_base_search`) — wrapper de `KnowledgeBaseSearchTool` pre-configurado para
PQR. Inyecta automáticamente el `processId` del proceso PQR en cada búsqueda, de modo que el agente nunca filtra por
proceso incorrecto. Si PQR no está configurado como proceso IA (`PqrIaGuard::getPqrProcessId() = null`), retorna error
en lugar de buscar sin filtro (evita exponer documentos de otros procesos).

**`PqrTool`** (`create_response_pqr`) — registra una respuesta oficial a una PQR. Recibe `documentId` (ID raíz de la
PQR), `subject` y `contentAnswers`. Crea el documento de respuesta con los datos del funcionario activo, tipo de
distribución y despedida por defecto. La guía de flujo completo (cuándo usarla, cómo buscar la PQR, confirmación,
manejo del resultado) está en el atributo `#[AsTool]` de la clase.

**`PqrStatsTool`** — estadísticas en tiempo real sobre la vista `vpqr`:

| Tool                        | Descripción                                                                                                                                                      |
|-----------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `get_pqr_available_filters` | Lista los campos disponibles para filtrar                                                                                                                        |
| `get_pqr_filter_options`    | Opciones de `sys_dependencia` y `sys_tipo` por nombre                                                                                                            |
| `count_pqr`                 | Cuenta PQRs con filtros y/o agrupación (`fecha`, `canal_recepcion`, `sys_estado`, `sys_fecha_vencimiento`, `sys_fecha_terminado`, `sys_tipo`, `sys_dependencia`) |

La descripción de `count_pqr` incluye la regla de invocación paralela con `knowledge_base_search` y la distinción
dependencia interna vs. tercero/remitente.

Todas las herramientas verifican `PqrIaGuard::isPqrEnabled()` antes de ejecutarse.

### 11.5 `PqrIaGuard` — fuente de verdad del módulo

`PqrIaGuard` centraliza la verificación de si el módulo PQR está configurado como proceso IA:

- `isPqrEnabled(): bool` — si existe al menos un `ia_process` vinculado al formato `pqr`.
- `getPqrProcessId(): ?int` — retorna el ID del proceso o `null` si no está configurado.
- `FORMAT_NAME = 'pqr'` — constante pública con el nombre del formato. Usada por `PqrAgentProvider`,
  `PqrFormatoJsonProcessor`, `PqrDocumentProcessor`, `PqrStatsTool` y `PqrIaGuard` mismo para evitar literales
  dispersos.

### 11.6 Servidor MCP

**`PqrMcpFormatProvider`** implementa `McpFormatProviderInterface` y registra el formato PQR en el servidor MCP. Permite
que agentes externos (Claude Desktop, Cursor, AWS AgentCore) radiquen PQRs autónomamente:

```
list_formats          → identifica idformato del PQR
get_format_structure  → conoce los campos requeridos
search_field_options  → resuelve lookups (ciudad, dependencia, localidad)
create_document       → radica la PQR
```

### 11.7 Procesador de formato para IA (`PqrFormatoJsonProcessor`)

Personaliza el JSON de estructura del formato PQR entregado al agente (`FormatoJsonProcessorInterface`):

- **Excluye** campos internos: `destino_interno`, `colilla`, `select_mensajeria`.
- **Agrega** campos `TYPE_DEPENDENCIA` y `TYPE_LOCALIDAD` como campos de lookup, con descriptor `search_field_options` (
  MCP) o endpoint REST (clientes HTTP).
- Metadatos de lookup **cacheados 1 semana** (`pqr_ia_lookup_fields`).

Para invalidar el caché tras modificar campos del formulario:

```bash
php bin/console cache:pool:delete cache.app pqr_ia_lookup_fields
```

### 11.8 Serialización para Knowledge Base

`PqrJsonForIA` y `PqrRespuestaDataJsonForIA` convierten documentos PQR y sus respuestas a JSON/Markdown para indexarlos
en AWS Bedrock Knowledge Base mediante `app:ia:full-sync`.

---

## 12. Frontend

El frontend usa Vue 3 + Pinia y se encuentra en `public/views/modules/pqr/`.

### Módulos compilados

| Módulo             | Entry point                    | Descripción                          |
|--------------------|--------------------------------|--------------------------------------|
| `pqr`              | `src/pqr/main.js`              | Formulario público de radicación     |
| `configuracionPqr` | `src/configuracionPqr/main.js` | Administración del formulario        |
| `respuestaPqr`     | `src/respuestaPqr/main.js`     | Formulario de respuesta al ciudadano |

### Tipos de campo en el formulario público

`Text`, `Textarea`, `Date`, `Select`, `Radio`, `Checkbox`, `File`, `Autocomplete`, `Tratamiento`, `Dependencia`,
`Localidad`.

### Compilar

```bash
cd public/views/modules/pqr
npm install
npm run build        # producción
npm run watch        # desarrollo
```

---

## 13. Webservice público

Al publicar el formulario (`PUT /api/pqr/form/publish`), el sistema genera automáticamente los archivos necesarios para
el webservice:

**URL pública:** `https://DOMINIO/ws/pqr`

El ciudadano accede, completa el formulario con los campos configurados y su PQR queda radicada en SAIA. No requiere
autenticación.

**Webservice de calificación:** `https://DOMINIO/ws/pqr/calificacion`

Permite al ciudadano calificar la respuesta recibida. Se accede desde el enlace enviado en el email de respuesta.

---

## 14. Personalización

La clase `Services/Customizable/PqrCustomizable.php` es la base extensible del módulo. Permite agregar funcionalidad
específica por cliente sin modificar el core del bundle. Extiende esta clase para sobreescribir comportamientos
puntuales del ciclo de vida de la PQR.

Los servicios IA (`IA/`) se cargan condicionalmente solo si el bundle `ia` está instalado. Esto se controla en
`Resources/config/ia_services.php` e `ia_routes.php`, de modo que el módulo funciona de forma completa sin IA.

---

## Autores

- **Andrés Agudelo** — [andres.agudelo@saiasoftware.com](mailto:andres.agudelo@saiasoftware.com)

## Licencia

Propietaria — [SAIA Software](https://www.saiasoftware.com/)

---

*Última actualización: 2026-04-15 — arquitectura IA: PqrKnowledgeBaseSearchTool wrapper, PqrIaGuard::FORMAT_NAME, guía de tools en #[AsTool] descriptions, PqrAgentProvider simplificado*
