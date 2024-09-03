<?php

namespace App\Bundles\pqr\Services\crontab;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Exception\SaiaException;
use App\services\GlobalContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Saia\models\crontab\ICrontab;
use Throwable;

class ChangeStatusOfOportunoField implements ICrontab
{
    public function execute(): bool
    {
        $Connection = GlobalContainer::getConnection();
        $Logger = GlobalContainer::getLogger();
        $response = true;

        $statusOportuno = [
            FtPqr::OPORTUNO_PENDIENTES_SIN_VENCER,
            FtPqr::OPORTUNO_VENCIDAS_SIN_CERRAR
        ];

        $records = $Connection->createQueryBuilder()
            ->select('idft')
            ->from('vpqr')
            ->where("sys_oportuno IN (:oportuno)")
            ->setParameter(':oportuno', $statusOportuno, Connection::PARAM_STR_ARRAY)
            ->andWhere('sys_estado<>:status')
            ->setParameter(':status', FtPqr::ESTADO_INICIADO, Types::STRING)
            ->execute()->fetchAllAssociative();


        foreach ($records as $record) {
            $Connection->beginTransaction();
            try {
                $FtPqr = UtilitiesPqr::getInstanceForFtId($record['idft']);
                $Service = $FtPqr->getService();
                if (!$Service->updateSysOportuno()) {
                    throw new SaiaException($Service->getErrorManager()->getMessage());
                }
                $Connection->commit();
            } catch (Throwable $th) {
                $Connection->rollBack();
                $Logger->error($th->getMessage());
                $response = false;
            }

        }

        return $response;
    }

}