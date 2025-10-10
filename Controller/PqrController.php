<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Helper\Exception\ExceptionHelper;
use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\models\Dependencia;
use Saia\models\documento\Documento;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PqrController extends AbstractController
{
    use ExceptionHelper;

    #[Route('/searchByNumber', name: 'search', methods: ['GET'])]
    public function search(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        try {
            if (empty($request->get('numero'))) {
                throw new MissingParameterException("Se debe indicar el numero de radicado");
            }
            $email = trim($request->get('sys_email'));

            $Qb = $Connection
                ->createQueryBuilder()
                ->select('ft.*')
                ->from('ft_pqr', 'ft')
                ->join('ft', 'documento', 'd', 'ft.documento_iddocumento=d.iddocumento')
                ->where('d.estado<>:estado')
                ->setParameter('estado', Documento::ELIMINADO)
                ->andWhere('d.numero = :numero')
                ->setParameter('numero', $request->get('numero'), ParameterType::INTEGER);

            $records = FtPqr::findByQueryBuilder($Qb);

            $data = [];
            foreach ($records as $FtPqr) {
                if (mb_strtolower(trim($FtPqr->sys_email)) == mb_strtolower(trim($email))) {
                    $data[] = [
                        'fecha'       => DateController::convertDate($FtPqr->getDocument()->fecha),
                        'descripcion' => array_filter(explode("<br>", $FtPqr->getDocument()->getDescription())),
                        'url'         => $FtPqr->getService()->getUrlQR(),
                    ];
                }
            }

            $saiaResponse->replaceData($data);
        } catch (Throwable $th) {
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/historyForTimeline', name: 'getHistoryForTimeline', methods: ['GET'])]
    public function getHistoryForTimeline(
        Request $request,
        ISaiaResponse $saiaResponse,
    ): Response {
        try {
            $data = json_decode(CryptController::decrypt($request->get('infoCryp')));
            $FtPqr = UtilitiesPqr::getInstanceForDocumentId($data->documentId);

            if ($FtPqr->getPK() != $data->id) {
                throw new ValidationFailedException("La URL ingresada NO existe o ha sido eliminada");
            }

            $data = $FtPqr->getService()->getHistoryForTimeline();

            $saiaResponse->replaceData($data);
        } catch (Throwable $th) {
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/decrypt', name: 'decrypt', methods: ['GET'])]
    public function decrypt(
        Request $request,
        ISaiaResponse $saiaResponse,
    ): Response {
        try {
            if (!$request->get('dataCrypt')) {
                throw new MissingParameterException("Faltan parametros");
            }

            $data = json_decode(CryptController::decrypt($request->get('dataCrypt')), true);

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    #[Route('/contentDependencia', name: 'contentDependencia', methods: ['GET'])]
    public function contentDependencia(
        ISaiaResponse $saiaResponse,
        TranslatorInterface $translator,
    ): Response {
        try {
            $field = PqrFormField::FIELD_NAME_SYS_DEPENDENCIA;
            $PqrFormField = PqrFormField::findByAttributes([
                'name' => $field,
            ]);

            if (!$PqrFormField || !$PqrFormField->fk_campos_formato) {
                $trans = $translator->trans("no_esta_habilitado_campo_dependencia");
                throw new ValidationFailedException($trans);
            }

            $allDependency = Dependencia::findAllByAttributes();
            $options[] = "<option value='' data-i18n='g.seleccione'>Por favor Seleccione ...</option>";
            foreach ($allDependency as $Dependencia) {
                $options[] = "<option value='{$Dependencia->getPK()}'>$Dependencia->nombre</option>";
            }
            $options = implode('', $options);

            $i18n = "data-i18n='{$PqrFormField->getCamposFormato()->getFormat()->getKeyTranslatorAttribute()}.campos.{$PqrFormField->getCamposFormato()->nombre}'";
            $html = <<<HTML
                <div class='form-group form-group-default form-group-default-select2'>
                    <label $i18n>$PqrFormField->label</label>
                    <div class='form-group'>
                        <select class='full-width' name='bqCampo_$field' id='$field'>
                           $options
                        </select>
                        <input type="hidden" value="=" name="bqCondicional_$field">
                        <input type="hidden" value="1" name="bqNumerico_$field">
                    </div>
                </div>
                HTML;


            $data = [
                'content' => $html,
            ];
            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
