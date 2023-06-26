<?php

namespace App\Bundles\pqr\Services\crontab;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\services\exception\SaiaException;
use App\services\GlobalContainer;
use Doctrine\DBAL\Connection;
use Saia\models\crontab\ICrontab;
use Throwable;

class ChangeStatusOfOportunoField implements ICrontab
{
    public function execute(): bool
    {
        $Connection = GlobalContainer::getConnection();
        $Logger = GlobalContainer::getLogger();
        $response = true;

        $status = [
            FtPqr::OPORTUNO_PENDIENTES_SIN_VENCER,
            FtPqr::OPORTUNO_VENCIDAS_SIN_CERRAR
        ];

        $records = $Connection->createQueryBuilder()
            ->select('idft')
            ->from('vpqr')
            ->where("sys_oportuno IN (:status)")
            ->setParameter(':status', $status, Connection::PARAM_STR_ARRAY)
            ->execute()->fetchAllAssociative();


        foreach ($records as $record) {
            $Connection->beginTransaction();
            try {
                $FtPqr = new FtPqr($record['idft']);
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