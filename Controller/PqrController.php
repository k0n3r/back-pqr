<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use Saia\models\formatos\CamposFormato;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\models\Dependencia;
use Saia\models\documento\Documento;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PqrController extends AbstractController
{
    #[Route('/searchByNumber', name: 'search', methods: ['GET'])]
    public function search(
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            if (empty($request->query->get('numero'))) {
                throw new MissingParameterException("Se debe indicar el numero de radicado");
            }
            $email = trim($request->query->get('sys_email'));

            $Qb = $Connection
                ->createQueryBuilder()
                ->select('ft.*')
                ->from('ft_pqr', 'ft')
                ->join('ft', 'documento', 'd', 'ft.documento_iddocumento=d.iddocumento')
                ->where('d.estado<>:estado')
                ->setParameter('estado', Documento::ELIMINADO)
                ->andWhere('d.numero = :numero')
                ->setParameter('numero', $request->query->get('numero'), ParameterType::INTEGER);

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

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/historyForTimeline', name: 'getHistoryForTimeline', methods: ['GET'])]
    public function getHistoryForTimeline(
        Request $request,
        jsonResponseService $json,
    ): Response {
        try {
            $data  = json_decode(CryptController::decrypt($request->query->get('infoCryp')));
            $FtPqr = UtilitiesPqr::getInstanceForDocumentId($data->documentId);

            if ($FtPqr->getPK() != $data->id) {
                throw new ValidationFailedException("La URL ingresada NO existe o ha sido eliminada");
            }

            $data = $FtPqr->getService()->getHistoryForTimeline();

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/decrypt', name: 'decrypt', methods: ['GET'])]
    public function decrypt(
        Request $request,
        jsonResponseService $json,
    ): Response {
        try {
            if (!$request->query->get('dataCrypt')) {
                throw new MissingParameterException("Faltan parametros");
            }

            $data = json_decode(CryptController::decrypt($request->query->get('dataCrypt')), true);

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/contentDependencia', name: 'contentDependencia', methods: ['GET'])]
    public function contentDependencia(
        jsonResponseService $json,
        TranslatorInterface $translator,
        PqrFormFieldRepository $pqrFormFieldRepository,
    ): Response {
        try {
            $field        = PqrFormField::FIELD_NAME_SYS_DEPENDENCIA;
            $pqrFormField = $pqrFormFieldRepository->findOneBy(['name' => $field]);

            if (!$pqrFormField || !$pqrFormField->getFkCamposFormato()) {
                $message = $translator->trans("no_esta_habilitado_campo_dependencia");

                return $json->success(['enabled' => 0], $message);
            }

            $camposFormato = new CamposFormato($pqrFormField->getFkCamposFormato());
            $allDependency = Dependencia::findAllByAttributes();
            $options[]     = "<option value='' data-i18n='g.seleccione'>Por favor Seleccione ...</option>";
            foreach ($allDependency as $Dependencia) {
                $options[] = "<option value='{$Dependencia->getPK()}'>$Dependencia->nombre</option>";
            }
            $options = implode('', $options);

            $i18n = "data-i18n='{$camposFormato->getFormat()->getKeyTranslatorAttribute()}.campos.$camposFormato->nombre'";
            $html = <<<HTML
                <div class='form-group form-group-default form-group-default-select2'>
                    <label $i18n>{$pqrFormField->getLabel()}</label>
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
                'enabled' => 1,
                'content' => $html,
            ];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }
}
