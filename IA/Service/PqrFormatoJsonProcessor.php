<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\FormatoJsonProcessorInterface;
use App\Bundles\ia\Services\FormatoJsonService;
use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Bundles\pqr\Service\PqrFormProvider;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PqrFormatoJsonProcessor implements FormatoJsonProcessorInterface
{
    private const int CACHE_TTL = 604800; // 1 semana en segundos

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly PqrFormProvider $pqrFormProvider,
    ) {
    }

    public function getFormatName(): string
    {
        return PqrIaGuard::FORMAT_NAME;
    }

    /**
     * @inheritDoc
     */
    public static function getPriority(): int
    {
        return 0;
    }

    public function customize(FormatoJsonService $service): void
    {
        $service->excludeFields([
            'destino_interno',
            'colilla',
            'select_mensajeria',
        ]);

        $lookupFields = $this->cache->get('pqr_ia_lookup_fields', function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            $result = [];
            foreach ($this->pqrFormProvider->getFields() as $field) {
                $htmlField = $field->getHtmlField();
                if ($htmlField && (
                    $htmlField->getType() === PqrHtmlField::TYPE_DEPENDENCIA ||
                    $htmlField->getType() === PqrHtmlField::TYPE_LOCALIDAD
                )) {
                    $result[] = [
                        'name'     => $field->getName(),
                        'label'    => $field->getLabel(),
                        'required' => $field->isRequired(),
                    ];
                }
            }

            return $result;
        });

        foreach ($lookupFields as $fieldMeta) {
            $service->addFields([
                $service->makeLookupField($fieldMeta['name'], $fieldMeta['label'], $fieldMeta['required']),
            ]);
        }
    }
}
