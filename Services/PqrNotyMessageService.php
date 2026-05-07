<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use Saia\controllers\functions\Header;

readonly class PqrNotyMessageService
{
    public function __construct(
        private PqrNotyMessageRepository $repository,
    ) {
    }

    public function update(int $id, array $data): void
    {
        $entity = $this->repository->find($id);
        if (!$entity) {
            return;
        }

        if (array_key_exists('subject', $data)) {
            $entity->setSubject($data['subject']);
        }
        if (array_key_exists('message_body', $data)) {
            $entity->setMessageBody($data['message_body']);
        }

        $this->repository->update();
    }

    /**
     * Obtiene los registros para actualizar el cuerpo de las notificaciones
     *
     * @return array
     */
    public function getDataPqrNotyMessages(): array
    {
        $data = [];
        foreach ($this->repository->findBy(['active' => true]) as $msg) {
            $data[] = [
                'text'  => $msg->getLabel(),
                'value' => [
                    'id'           => $msg->getId(),
                    'description'  => $msg->getDescription(),
                    'subject'      => $msg->getSubject(),
                    'message_body' => $msg->getMessageBody(),
                    'type'         => $msg->getType(),
                ],
            ];
        }

        return $data;
    }

    /**
     * Resuelve y reemplaza las variables por los valores
     */
    public static function resolveVariables(string $baseContent, FtPqr $FtPqr): string
    {
        $functions = Header::getFunctionsFromString($baseContent);
        $functions = str_replace(['{*', '*}'], '', $functions);

        foreach ($functions as $variable) {
            $value       = call_user_func([self::class, $variable], $FtPqr);
            $baseContent = str_replace("{*$variable*}", $value, $baseContent);
        }

        return $baseContent;
    }

    public static function n_radicadoPqr(FtPqr $FtPqr): string
    {
        return $FtPqr->getDocument()->getService()->getFilingReferenceNumber();
    }

    public static function n_nombreFormularioPqr(FtPqr $FtPqr): string
    {
        return $FtPqr->getService()->getPqrForm()->label;
    }

    public static function n_consecutivoPqr(FtPqr $FtPqr): string|int
    {
        return $FtPqr->getDocument()->numero;
    }
}
