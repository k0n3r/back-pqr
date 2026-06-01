<?php

namespace App\Bundles\pqr\Command;

use App\Command\GenerateJsonTranslatorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:generate:json-translator-for-pqr',
    description: 'Genera un archivo JSON por defecto con traducciones de PQR'
)]
class GenerateJsonTranslatorForPQRCommand extends GenerateJsonTranslatorCommand
{
    protected function getJsonContent(): string
    {
        return <<<JSON
{
  "formatos": {
    "formato_pqr": {
      "campos": {
        "sys_tercero": "Response Recipient",
        "sys_severidad": "PQR Severity",
        "sys_oportuno": "Timeliness in responses",
        "sys_impacto": "PQR Impact",
        "radicacion": "Filing",
        "sys_frecuencia": "PQR Frequency",
        "sys_fecha_vencimiento": "PQR Due Date",
        "sys_anonimo": "Anonymous",
        "sys_fecha_terminado": "PQR Completion Date",
        "sys_estado": "PQR Status",
        "sys_tipo": "Type",
        "sys_email": "E-mail",
        "sys_folios": "Number of sheets",
        "sys_anexos": "Attachments",
        "distribucion": "Distribution",
        "destino_interno": "Send internally",
        "select_mensajeria": "Distribution Type",
        "descripcion": "Subject",
        "colilla": "Orientation of the received stamp",
        "digitalizacion": "Scanning"
      },
      "nombre_formato": "PQRSF"
    },
    "formato_pqr_respuesta": {
      "campos": {
        "ciudad_origen": "City of Origin",
        "destino": "Destination",
        "tipo_distribucion": "Distribution Type",
        "copia": "CC",
        "asunto": "Subject",
        "contenido": "Content",
        "despedida": "Closing",
        "otra_despedida": "Write the closing",
        "anexos_digitales": "Digital Attachments",
        "anexos_fisicos": "Physical Attachments",
        "ver_copia": "Show Internal Copy",
        "copia_interna": "BCC",
        "sol_encuesta": "Request the service survey",
        "cerrar_tareas": "Do you want to close the PQRSF tasks?"
      },
      "nombre_formato": "EXTERNAL COMMUNICATION (PQRSF)"
    },
    "formato_pqr_calificacion": {
      "campos": {
        "experiencia_gestion": "Rate your experience with the management of your Petition, Complaint, Claim or Request",
        "experiencia_servicio": "Rate your overall experience regarding the services you have received"
      },
      "nombre_formato": "RATING (PQRSF)"
    }
  }
}
JSON;
    }

    protected function getJsonFilePath(): string
    {
        return $this->pathResolver->absolutePublicPath('views/modules/pqr/translations/en_tmp.json');
    }
}
