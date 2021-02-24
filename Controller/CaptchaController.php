<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\EventSubscriber\middlewares\IHasCaptcha;
use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Exception;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @Route("/captcha", name="captcha_")
 */
class CaptchaController extends AbstractController implements IHasCaptcha
{
    /**
     * @Route("/saveDocument", name="register", methods={"POST"})
     * @return Response
     */
    public function save(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response
    {
        try {
            $Connection->beginTransaction();

            if (empty($Request->get('formatId'))) {
                throw new Exception("Se debe indicar el formato", 1);
            }

            if (empty($Request->get('dependencia'))) {
                throw new Exception("Debe indicar el rol del creador", 1);
            }

            $VfuncionarioDc = VfuncionarioDc::findByRole($Request->get('dependencia'));
            if (!$VfuncionarioDc) {
                throw new Exception("Rol del creador incorrecto", 1);
            }

            $Formato = new Formato($Request->get('formatId'));
            $SaveDocument = new SaveDocument($Formato, $VfuncionarioDc);
            if (!$SaveDocument->create($Request->request->all())) {
                throw new Exception("No fue posible generar el documento");
            }

            $Documento = $SaveDocument->getDocument();

            $message = "<br/>Su solicitud ha sido generada con el número de radicado <strong>{$Documento->numero}</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";
            if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
                'name' => 'ws_noty_radicado'
            ])) {
                $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $Documento->getFt());
            }

            $attributes = [
                'messageBody' => $message,
                'number' => $Documento->numero,
            ];

            $saiaResponse->replaceData($attributes);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }
        return $saiaResponse->getResponse();
    }
}