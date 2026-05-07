<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\EventSubscriber\middlewares\IHasCaptcha;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/captcha', name: 'captcha_')]
class CaptchaController extends AbstractController implements IHasCaptcha
{
    /**
     * @param Request $Request
     * @param jsonResponseService $json
     * @param Connection $Connection
     * @param TranslatorInterface $translator
     * @return Response
     */
    #[Route('/saveDocument', name: 'register', methods: ['POST'])]
    public function saveDocument(
        Request $Request,
        jsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
        PqrNotyMessageRepository $pqrNotyMessageRepository,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (empty($Request->request->get('formatId'))) {
                $trans = $translator->trans('indicar_formato');
                throw new MissingParameterException($trans);
            }

            if (empty($Request->request->get('dependencia'))) {
                $trans = $translator->trans('indicar_rol_creador');
                throw new MissingParameterException($trans);
            }

            $VfuncionarioDc = VfuncionarioDc::findByRole($Request->request->get('dependencia'));
            if (!$VfuncionarioDc) {
                $trans = $translator->trans('rol_creador_incorrecto');
                throw new MissingParameterException($trans);
            }

            $Request->request->set('webservice', 1);
            $Formato = new Formato($Request->request->get('formatId'));
            if ($Formato->isRequiredGeolocation() && empty($Request->request->get('geolocalizacion'))) {
                $trans = $translator->trans('debe_permitir_geolocalizacion');
                throw new ValidationFailedException($trans);
            }

            $SaveDocument = new SaveDocument($Formato, $VfuncionarioDc);
            if (!$SaveDocument->create($Request->request->all())) {
                $trans = $translator->trans('no_fue_posible_generar_documento');
                throw new ValidationFailedException($trans);
            }

            $Documento = $SaveDocument->getDocument();

            $message = "<br/>Su solicitud ha sido generada con el número de radicado <strong>$Documento->numero</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";
            $pqrNotyMessage = $pqrNotyMessageRepository->findByName('ws_noty_radicado');
            if ($pqrNotyMessage) {
                $message = PqrNotyMessageService::resolveVariables($pqrNotyMessage->getMessageBody() ?? '', $Documento->getFt());
            }

            $attributes = [
                'messageBody' => $message,
                'number'      => $Documento->numero,
            ];

            $Connection->commit();

            return $json->success($attributes);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
