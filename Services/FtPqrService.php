<?php

namespace App\Bundles\pqr\Services;

use Saia\models\Funcionario;
use Saia\models\formatos\Formato;
use Saia\controllers\DateController;
use Saia\controllers\documento\SaveFt;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\SessionController;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;

class FtPqrService
{

    private FtPqr $FtPqr;
    private PqrService $PqrService;
    private string $errorMessage;
    private Funcionario $Funcionario;

    public function __construct(FtPqr $FtPqr)
    {
        $this->FtPqr = $FtPqr;
        $this->PqrService = new PqrService();
        $this->Funcionario = SessionController::getUser();
    }

    /**
     * Retorna el mensaje de error
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Obtiene la instancia de FtPqr actualizada
     *
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): FtPqr
    {
        return $this->FtPqr;
    }

    /**
     * Obtiene los campos a cargar en el adicionar
     * de la respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataToLoadResponse(): array
    {

        if ($Tercero = $this->FtPqr->Tercero) {
            $destino = [
                'id' => $Tercero->getPK(),
                'text' => "{$Tercero->identificacion} - {$Tercero->nombre}"
            ];
        };

        $Formato = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ]);

        if ($records = $Formato->getField('tipo_distribucion')->CampoOpciones) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL) {
                    $tipoDistribucion = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        if ($records = $Formato->getField('despedida')->CampoOpciones) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::ATENTAMENTE_DESPEDIDA) {
                    $despedida = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        return [
            'iddocPqr' => $this->FtPqr->Documento->getPK(),
            'destino' => $destino ?? 0,
            'tipo_distribucion' => $tipoDistribucion ?? 0,
            'despedida' => $despedida ?? 0,
            'asunto' => "Respondiendo a la {$this->FtPqr->getFormat()->etiqueta} No {$this->FtPqr->Documento->numero}"
        ];
    }

    /**
     * Termina una PQR
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function finish(string $observaciones = ''): bool
    {
        return $this->FtPqr->changeStatus(
            FtPqr::ESTADO_TERMINADO,
            $observaciones
        );
    }

    /**
     * Obtiene los registros del historial
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getRecordsHistory(): array
    {
        $rows = [];

        foreach ($this->FtPqr->getHistory() as $PqrHistory) {
            $rows[] = array_merge(
                $PqrHistory->getDataAttributes(),
                [
                    'nombre_funcionario' => $PqrHistory->Funcionario->getName()
                ]
            );
        }

        return $rows;
    }

    /**
     * Obtiene el email
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    public function getHistoryForTimeline(): array
    {

        $rows = [];

        $records = $this->FtPqr->getHistory('fecha asc');
        $expirationDate = $this->getExpirationDate();
        $addExpiration = false;

        $rows[] = $this->getInitialRequestData();

        foreach ($records as $PqrHistory) {
            $action = DateController::convertDate($PqrHistory->fecha, 'Y-m-d');
            $actionDate = new \DateTime($action);

            if ($actionDate > $expirationDate && !$addExpiration) {
                $rows[] =  $this->getDataFinish();
                $addExpiration = true;
            }

            if ($row = $PqrHistory->getService()->getHistoryForTimeline()) {
                $rows[] = $row;
            }
        }

        if (!$addExpiration) {
            $rows[] = $this->getDataFinish();
        }

        return $rows;
    }

    /**
     * Obtiene la fecha de expiracion
     *
     * @return DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getExpirationDate(): \DateTime
    {
        $expiration = DateController::convertDate($this->FtPqr->sys_fecha_vencimiento, 'Y-m-d');

        return new \DateTime($expiration);
    }

    /**
     * Retonar la informacion inicial de la solicitud para el de timeline
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getInitialRequestData(): array
    {
        return [
            'iconPoint' => 'fa fa-map-marker',
            'iconPointColor' => 'success',
            'date' => DateController::convertDate($this->FtPqr->Documento->fecha),
            'description' => "Se registra la solicitud No # {$this->FtPqr->Documento->numero}",
            'url' => UtilitiesPqr::getRoutePdf($this->FtPqr->Documento)
        ];
    }

    /**
     * Obtiene los datoss de finalizacion de timeline
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDataFinish(): array
    {
        $type = $this->FtPqr->getFieldValue('sys_tipo');
        return [
            'iconPoint' => 'fa fa-flag-checkered',
            'iconPointColor' => 'success',
            'date' => DateController::convertDate(
                $this->getExpirationDate(),
                DateController::PUBLIC_DATE_FORMAT
            ),
            'description' => "Fecha maxima para dar respuesta a la solicitud de tipo {$type}"
        ];
    }

    /**
     * Actualiza el tipo de PQR y guarda en el historial
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateType(array $data): bool
    {

        if (!$data['type']) {
            $this->errorMessage = "Error faltan parametros";
            return false;
        }

        if ($this->PqrService->subTypeExist() && !$data['subtype']) {
            $this->errorMessage = "Error faltan parametros";
            return false;
        }

        $newAttributes = [];
        if ($data['type'] != $this->FtPqr->sys_tipo) {
            $oldType = $this->FtPqr->getFieldValue('sys_tipo');
            $newAttributes['sys_tipo'] = $data['type'];
            $textField[] = "tipo de {$oldType} a {newType}";
        }

        if ($this->PqrService->subTypeExist()) {
            if ($data['subtype'] != $this->FtPqr->sys_subtipo) {
                $oldSubType = $this->FtPqr->getFieldValue('sys_subtipo');
                $newAttributes['sys_subtipo'] = $data['subtype'];
                $textField[] = "categoria/subtipo de {$oldSubType} a {newSubType}";
            }
        }

        if ($this->PqrService->dependencyExist()) {
            if ($data['dependency'] != $this->FtPqr->sys_dependencia) {
                $oldDependency = $this->FtPqr->getValueForReport('sys_dependencia');
                $newAttributes['sys_dependencia'] = $data['dependency'];
                $textField[] = "dependencia de {$oldDependency} a {newDependency}";
            }
        }

        $expiration = DateController::convertDate($this->FtPqr->sys_fecha_vencimiento, 'Y-m-d');
        if ($data['expirationDate'] != $expiration) {

            $newAttributes['sys_fecha_vencimiento'] = $data['expirationDate'];
            $this->FtPqr->Documento->fecha_limite = $data['expirationDate'];
            $this->FtPqr->Documento->update();

            $oldDate = DateController::convertDate(
                $expiration,
                DateController::PUBLIC_DATE_FORMAT,
                'Y-m-d'
            );

            $newDate = DateController::convertDate(
                $data['expirationDate'],
                DateController::PUBLIC_DATE_FORMAT,
                'Y-m-d'
            );
            $textField[] = "fecha de vencimiento de {$oldDate} a {$newDate}";
        }

        $SaveFt = new SaveFt($this->FtPqr->Documento);
        $SaveFt->edit($newAttributes);
        $this->FtPqr = $this->FtPqr->Documento->getFt();

        $text = "Se actualiza: " . implode(', ', $textField);
        $newType = $this->FtPqr->getFieldValue('sys_tipo');
        $newSubType = $this->PqrService->subTypeExist() ? $this->FtPqr->getFieldValue('sys_subtipo') : '';
        $newDependency = $this->PqrService->dependencyExist() ? $this->FtPqr->getValueForReport('sys_dependencia') : '';

        $text = str_replace([
            '{newType}',
            '{newSubType}',
            '{newDependency}'
        ], [
            $newType,
            $newSubType,
            $newDependency
        ], $text);

        $history = [
            'fecha' => date('Y-m-d H:i:s'),
            'idft' => $this->FtPqr->getPK(),
            'fk_funcionario' => $this->Funcionario->getPK(),
            'tipo' => PqrHistory::TIPO_CAMBIO_ESTADO,
            'idfk' => 0,
            'descripcion' => $text
        ];

        return PqrHistory::newRecord($history);
    }
}
