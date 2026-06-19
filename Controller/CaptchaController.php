<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Bundles\pqr\Service\PqrNotyMessageService;
use App\EventSubscriber\middlewares\IHasCaptcha;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
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
     * @param Request                  $request
     * @param jsonResponseService      $json
     * @param Connection               $Connection
     * @param TranslatorInterface      $translator
     * @param PqrNotyMessageRepository $pqrNotyMessageRepository
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/saveDocument', name: 'register', methods: ['POST'])]
    public function saveDocument(
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
        PqrNotyMessageRepository $pqrNotyMessageRepository,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (empty($request->request->get('formatId'))) {
                $trans = $translator->trans('indicar_formato');
                throw new MissingParameterException($trans);
            }

            if (empty($request->request->get('dependencia'))) {
                $trans = $translator->trans('indicar_rol_creador');
                throw new MissingParameterException($trans);
            }

            $VfuncionarioDc = VfuncionarioDc::findByRole((int)$request->request->get('dependencia'));
            if (!$VfuncionarioDc) {
                $trans = $translator->trans('rol_creador_incorrecto');
                throw new MissingParameterException($trans);
            }

            $request->request->set('webservice', 1);
            $Formato = new Formato($request->request->get('formatId'));
            if ($Formato->isRequiredGeolocation() && empty($request->request->get('geolocalizacion'))) {
                $trans = $translator->trans('debe_permitir_geolocalizacion');
                throw new ValidationFailedException($trans);
            }

            $SaveDocument = new SaveDocument($Formato, $VfuncionarioDc);
            $SaveDocument->createOrUpdateDocument($request->request->all());
            $Documento = $SaveDocument->getDocument();

            $message        = "<br/>Su solicitud ha sido generada con el número de radicado <strong>$Documento->numero</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";
            $pqrNotyMessage = $pqrNotyMessageRepository->findByName('ws_noty_radicado');
            if ($pqrNotyMessage) {
                $message = PqrNotyMessageService::resolveVariables(
                    $pqrNotyMessage->getMessageBody() ?? '',
                    $Documento->getFt(),
                );
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
