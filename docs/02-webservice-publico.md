# PQR — Webservice público

El webservice público permite a ciudadanos radicar PQRs sin autenticación, desde una URL accesible en internet.

---

## URLs

| URL | Descripción |
|-----|-------------|
| `https://DOMINIO/ws/pqr` | Formulario de radicación |
| `https://DOMINIO/ws/pqr/calificacion` | Calificación de la respuesta recibida |
| `https://DOMINIO/ws/pqr/infoQR.html?data=<token>` | Página de seguimiento del radicado (enlace en email y WSO) |

---

## Flujo de radicación

1. Ciudadano accede a `https://DOMINIO/ws/pqr`
2. El sistema genera el formulario dinámico configurado por el administrador
3. Ciudadano completa los campos y envía
4. Sistema crea el documento PQR en SAIA
5. Sistema envía email de confirmación al ciudadano con número de radicado y URL de seguimiento
6. La PQR queda en estado `PENDIENTE` para gestión interna

---

## Generación del webservice

Al publicar el formulario (`PUT /api/pqr/form/publish`), el sistema genera automáticamente:

- Archivos de frontend (HTML, JS) para el formulario público
- Los endpoints REST del webservice
- Las validaciones según los campos configurados

No se requiere ninguna acción manual adicional.

---

## Tipos de campo soportados en el formulario público

| Tipo | Descripción |
|------|-------------|
| `Text` | Campo de texto libre |
| `Textarea` | Área de texto multilinea |
| `Date` | Selector de fecha |
| `Select` | Lista desplegable |
| `Radio` | Selección única |
| `Checkbox` | Selección múltiple |
| `File` | Carga de archivos |
| `Autocomplete` | Búsqueda con sugerencias |
| `Tratamiento` | Campo de tratamiento (Sr./Sra./etc.) |
| `Dependencia` | Selector de dependencia interna |
| `Localidad` | Selector de localidad geográfica |

---

## Endpoint de radicación pública

`POST /api/pqr/webservice/saveDocument`

Sin autenticación. Acepta el payload del formulario y crea la PQR.

Para radicación con CAPTCHA: `POST /api/pqr/captcha/saveDocument`

---

## Página de seguimiento (infoQR)

La URL de seguimiento se genera con un token cifrado que referencia el `ft_pqr.idft_pqr` y el
`documento.iddocumento`. La generación usa `CryptController::encrypt()` con el formato:

```json
{"id": <pqr_id>, "documentId": <document_id>}
```

Esta misma URL se expone en el flujo de consulta por WhatsApp (WSO) como botón CTA "Ver más información".
