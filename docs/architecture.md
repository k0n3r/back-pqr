# Arquitectura general

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

## Capas

| Capa | Responsabilidad |
|------|----------------|
| `Controller/` | Solo HTTP — recibe request, delega a Service, retorna JsonResponse |
| `Services/` | Lógica de negocio — ciclo de vida PQR, configuración, notificaciones |
| `Entity/` + `Repository/` | Persistencia ORM Doctrine — 9 entidades `pqr_*` |
| `formatos/` | Hooks del ciclo de vida del documento (FtPqr, FtPqrRespuesta, FtPqrCalificacion) |
| `IA/` | Integración condicional con bundle `ia` |

## Acceso a datos

- **Tablas `pqr_*`:** Doctrine ORM via `Entity/` + `Repository/`
- **Tablas `ft_pqr*`:** Active Record legacy (extienden `ModelFormat` del core SAIA)
- **Vistas SQL** (`vpqr`, `vpqr_tareas`): DBAL directo desde `FtPqrService`
- **`LegacyServiceLocator`:** puente hacia EntityManager y servicios Symfony desde clases no-DI

## Registro del bundle

El bundle se registra automáticamente vía `src/Bundles/pqr/config/bundle.php`. No requiere modificar `bundles.php`.
