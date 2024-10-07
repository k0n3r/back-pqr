<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Exception\SaiaException;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Types;
use Exception;
use Saia\controllers\DateController;
use Saia\models\Dependencia;
use Saia\models\documento\Documento;
use Saia\controllers\CryptController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

class PqrController extends AbstractController
{

    /**
     * @Route("/searchByNumber", name="search", methods={"GET"})
     */
    public function search(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            if (empty($request->get('numero'))) {
                throw new SaiaException("Se debe indicar el numero de radicado", 200);
            }
            $email = trim($request->get('sys_email'));

            $Connection = GlobalContainer::getConnection();

            $Qb = $Connection->createQueryBuilder()
                ->select('ft.*')
                ->from('ft_pqr', 'ft')
                ->join('ft', 'documento', 'd', 'ft.documento_iddocumento=d.iddocumento')
                ->where('d.estado<>:estado')
                ->setParameter(':estado', Documento::ELIMINADO, Types::STRING)
                ->andWhere('d.numero = :numero')
                ->setParameter(':numero', $request->get('numero'), Types::INTEGER);

            $records = FtPqr::findByQueryBuilder($Qb);

            $data = [];
            foreach ($records as $FtPqr) {
                if (mb_strtolower(trim($FtPqr->sys_email)) == mb_strtolower(trim($email))) {
                    $data[] = [
                        'fecha'       => DateController::convertDate($FtPqr->getDocument()->fecha),
                        'descripcion' => array_filter(explode("<br>", $FtPqr->getDocument()->getDescription())),
                        'url'         => $FtPqr->getService()->getUrlQR()
                    ];
                }
            }

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/historyForTimeline", name="getHistoryForTimeline", methods={"GET"})
     */
    public function getHistoryForTimeline(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = json_decode(CryptController::decrypt($request->get('infoCryp')));
            $FtPqr = UtilitiesPqr::getInstanceForDocumentId($data->documentId);

            if ($FtPqr->getPK() != $data->id) {
                throw new SaiaException("La URL ingresada NO existe o ha sido eliminada", 1);
            }

            $data = $FtPqr->getService()->getHistoryForTimeline();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/decrypt", name="decrypt", methods={"GET"})
     */
    public function decrypt(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {
        try {

            if (!$request->get('dataCrypt')) {
                throw new SaiaException("Faltan parametros", 1);
            }

            $data = json_decode(CryptController::decrypt($request->get('dataCrypt')), true);

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/contentDependencia", name="contentDependencia", methods={"GET"})
     */
    public function contentDependencia(
        ISaiaResponse $saiaResponse
    ): Response {
        try {

            $field = PqrFormField::FIELD_NAME_SYS_DEPENDENCIA;
            $PqrFormField = PqrFormField::findByAttributes([
                'name' => $field
            ]);

            if (!$PqrFormField || !$PqrFormField->fk_campos_formato) {
                throw new SaiaException("No esta habilitado el campo dependencia");
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
        </div>
    </div>
HTML;


            $data = [
                'content' => $html
            ];
            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);

        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
