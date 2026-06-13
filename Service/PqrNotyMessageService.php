<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Exception\ValidationFailedException;
use Saia\controllers\functions\Header;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PqrNotyMessageService
{
    public function __construct(
        private PqrNotyMessageRepository $repository,
        private TranslatorInterface $translator,
    ) {
    }

    public function update(int $id, array $data): void
    {
        $entity = $this->repository->find($id);
        if (!$entity) {
            throw new ValidationFailedException($this->translator->trans('notificacion_no_encontrada'));
        }

        if (array_key_exists('subject', $data)) {
            $entity->setSubject($data['subject']);
        }
        if (array_key_exists('message_body', $data)) {
            $entity->setMessageBody($data['message_body']);
        }

        $this->repository->update();
    }

    public function getDataPqrNotyMessages(): array
    {
        return array_map(
            static fn ($msg)
                => [
                'text'  => $msg->getLabel(),
                'value' => [
                    'id'           => $msg->getId(),
                    'description'  => $msg->getDescription(),
                    'subject'      => $msg->getSubject(),
                    'message_body' => $msg->getMessageBody(),
                    'type'         => $msg->getType(),
                ],
            ],
            $this->repository->findBy(['active' => true]),
        );
    }

    public static function resolveVariables(string $baseContent, FtPqr $FtPqr): string
    {
        $variables = [
            'n_radicadoPqr'         => fn () => $FtPqr->getDocument()->getService()->getFilingReferenceNumber(),
            'n_nombreFormularioPqr' => fn () => $FtPqr->getService()->getPqrForm()->getLabel(),
            'n_consecutivoPqr'      => fn () => (string)$FtPqr->getDocument()->numero,
        ];

        foreach (str_replace(['{*', '*}'], '', Header::getFunctionsFromString($baseContent)) as $variable) {
            if (isset($variables[$variable])) {
                $baseContent = str_replace("{*$variable*}", ($variables[$variable])(), $baseContent);
            }
        }

        return $baseContent;
    }
}
