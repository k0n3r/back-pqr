# Frontend

El frontend usa Vue 3 + Pinia y se encuentra en `public/views/modules/pqr/`.

## Módulos compilados

| Módulo | Entry point | Descripción |
|---|---|---|
| `pqr` | `src/pqr/main.js` | Formulario público de radicación |
| `configuracionPqr` | `src/configuracionPqr/main.js` | Administración del formulario |
| `respuestaPqr` | `src/respuestaPqr/main.js` | Formulario de respuesta al ciudadano |

## Tipos de campo en el formulario público

`Text`, `Textarea`, `Date`, `Select`, `Radio`, `Checkbox`, `File`, `Autocomplete`, `Tratamiento`, `Dependencia`, `Localidad`.

## Compilar

```bash
cd public/views/modules/pqr
npm install
npm run build    # producción
npm run watch    # desarrollo
```
