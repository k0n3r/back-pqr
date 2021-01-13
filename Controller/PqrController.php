<?php

namespace App\Bundles\pqr\Controller;

use Doctrine\DBAL\Types\Types;
use Saia\core\DatabaseConnection;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\controllers\CryptController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrController extends AbstractController
{

    /**
     * @Route("/searchByNumber", name="search", methods={"GET"})
     */
    public function searchByNumber(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            if (empty($request->get('numero'))) {
                throw new \Exception("Se debe indicar el numero de radicado", 200);
            }

            $Connection = DatabaseConnection::getDefaultConnection();

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
                $data[] = [
                    'fecha' => DateController::convertDate($FtPqr->Documento->fecha),
                    'descripcion' => array_filter(explode("<br>", $FtPqr->Documento->getDescription())),
                    'url' => $FtPqr->getUrlQR()
                ];
            }

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
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
            $FtPqr = FtPqr::findByDocumentId($data->documentId);

            if ($FtPqr->getPK() != $data->id) {
                throw new \Exception("La URL ingresada NO existe o ha sido eliminada", 1);
            }

            $data = $FtPqr->getService()->getHistoryForTimeline();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
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
                throw new \Exception("Faltan parametros", 1);
            }

            $data = json_decode(CryptController::decrypt($request->get('dataCrypt')), true);

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
