<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Services\crontab;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Contract\ScheduledTaskInterface;
use App\Service\UserLoginService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

readonly class ChangeStatusOfOportunoField implements ScheduledTaskInterface
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private UserLoginService $userLoginService,
    ) {}

    public function execute(): bool
    {
        $this->userLoginService->loginUserIfNotAuthenticated();

        $response = true;

        $records = $this->connection
            ->createQueryBuilder()
            ->select('idft')
            ->from('vpqr')
            ->where('sys_oportuno IN (:oportuno)')
            ->setParameter('oportuno', [
                FtPqr::OPORTUNO_PENDIENTES_SIN_VENCER,
                FtPqr::OPORTUNO_VENCIDAS_SIN_CERRAR,
            ], ArrayParameterType::STRING)
            ->andWhere('sys_estado<>:status')
            ->setParameter('status', FtPqr::ESTADO_INICIADO)
            ->executeQuery()->fetchAllAssociative();

        foreach ($records as $record) {
            $this->connection->beginTransaction();
            try {
                $FtPqr   = UtilitiesPqr::getInstanceForFtId($record['idft']);
                $Service = $FtPqr->getService();
                if (!$Service->updateSysOportuno()) {
                    throw new RuntimeException($Service->getErrorManager()->getMessage());
                }
                $this->connection->commit();
            } catch (Throwable $th) {
                $this->connection->rollBack();
                $this->logger->error($th->getMessage());
                $response = false;
            }
        }

        return $response;
    }
}
