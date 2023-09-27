<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Types;
use Exception;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\controllers\CryptController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\formatos\CamposFormato;
use Doctrine\DBAL\Connection;
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
                throw new Exception("Se debe indicar el numero de radicado", 200);
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
                if (trim($FtPqr->sys_email) == $email) {
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
                throw new Exception("La URL ingresada NO existe o ha sido eliminada", 1);
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
                throw new Exception("Faltan parametros", 1);
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
     * Actualiza el campo tipo descripcion de la pqr adicional al tipo.
     * 
     * @Route("/descriptionField/{fieldId}", name="descriptionField", methods={"PUT"})
     * @author Julian Otalvaro <julian.otalvaro@cerok.com>
     * @since 2023-09-26
     */
    public function descriptionField(
        int $fieldId,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {
        $Connection->beginTransaction();

        try {
            $PqrForms = PqrForm::findByAttributes([
                'active' => 1
            ]);

            if (!$PqrForms->description_field || (int)$PqrForms->description_field !== $fieldId) {
                //Nuevo campo descripcion
                $PqrFormField = new PqrFormField($fieldId);
                $CamposFormato = new CamposFormato($PqrFormField->fk_campos_formato);

                $actionList = explode(',', $CamposFormato->acciones);

                if (!in_array('p', $actionList)) {
                    array_push($actionList, 'p');
                }

                $CamposFormato->getService()->save([
                    'acciones' => implode(',', $actionList)
                ]);

                if ($PqrForms->description_field) {
                    //Se desactiva el campo descripcion anterior
                    $PqrFormFieldOld = new PqrFormField($PqrForms->description_field);
                    $CamposFormatoOld = new CamposFormato($PqrFormFieldOld->fk_campos_formato);
                    $arrayActionOld = explode(',', $CamposFormatoOld->acciones);
                    $actionListOld = array_diff($arrayActionOld, ['p']);

                    //Se guardan los cambios
                    $CamposFormatoOld->getService()->save([
                        'acciones' => implode(',', $actionListOld)
                    ]);
                }

                $PqrForms->getService()->save([
                    'description_field' => $fieldId
                ]);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
