<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\EventSubscriber\middlewares\IHasCaptcha;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Helper\Exception\ExceptionHelper;
use App\services\GlobalContainer;
use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/captcha', name: 'captcha_')]
class CaptchaController extends AbstractController implements IHasCaptcha
{
    use ExceptionHelper;

    /**
     * @param Request $Request
     * @param ISaiaResponse $saiaResponse
     * @param Connection $Connection
     * @return Response
     */
    #[Route('/saveDocument', name: 'register', methods: ['POST'])]
    public function saveDocument(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (empty($Request->get('formatId'))) {
                $trans = GlobalContainer::getTranslator()->trans('indicar_formato');
                throw new MissingParameterException($trans);
            }

            if (empty($Request->get('dependencia'))) {
                $trans = GlobalContainer::getTranslator()->trans('indicar_rol_creador');
                throw new MissingParameterException($trans);
            }

            $VfuncionarioDc = VfuncionarioDc::findByRole($Request->get('dependencia'));
            if (!$VfuncionarioDc) {
                $trans = GlobalContainer::getTranslator()->trans('rol_creador_incorrecto');
                throw new MissingParameterException($trans);
            }

            $Request->request->set('webservice', 1);
            $Formato = new Formato($Request->get('formatId'));
            if ($Formato->isRequiredGeolocation() && empty($Request->get('geolocalizacion'))) {
                $trans = GlobalContainer::getTranslator()->trans('debe_permitir_geolocalizacion');
                throw new ValidationFailedException($trans);
            }

            $SaveDocument = new SaveDocument($Formato, $VfuncionarioDc);
            if (!$SaveDocument->create($Request->request->all())) {
                $trans = GlobalContainer::getTranslator()->trans('no_fue_posible_generar_documento');
                throw new ValidationFailedException($trans);
            }

            $Documento = $SaveDocument->getDocument();

            $message = "<br/>Su solicitud ha sido generada con el número de radicado <strong>$Documento->numero</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";
            if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
                'name' => 'ws_noty_radicado',
            ])) {
                $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $Documento->getFt());
            }

            $attributes = [
                'messageBody' => $message,
                'number'      => $Documento->numero,
            ];

            $saiaResponse->replaceData($attributes);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }
}
