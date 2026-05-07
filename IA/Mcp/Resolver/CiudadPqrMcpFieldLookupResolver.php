<?php

namespace App\Bundles\pqr\IA\Mcp\Resolver;

use App\Bundles\ia\Mcp\McpFieldLookupResolverInterface;
use App\Bundles\ia\Mcp\Traits\McpCiudadLookupTrait;
use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Bundles\pqr\Repository\PqrHtmlFieldRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use Saia\models\formatos\CamposFormato;

class CiudadPqrMcpFieldLookupResolver implements McpFieldLookupResolverInterface
{
    use McpCiudadLookupTrait;

    public function __construct(
        private readonly PqrFormProvider $pqrFormProvider,
        private readonly PqrHtmlFieldRepository $pqrHtmlFieldRepository,
    ) {
    }

    public function supports(string $fieldName, int $idformato): bool
    {
        $campos = CamposFormato::findByAttributes([
            'nombre'            => $fieldName,
            'formato_idformato' => $idformato,
            'etiqueta_html'     => 'Method',
        ]);

        if (is_null($campos)) {
            return false;
        }

        $field = $this->pqrFormProvider->getFieldByName($campos->nombre);
        if (is_null($field)) {
            return false;
        }

        $htmlField = $this->pqrHtmlFieldRepository->find($field->getFkPqrHtmlField());

        return $htmlField?->getType() === PqrHtmlField::TYPE_LOCALIDAD;
    }

    public function search(string $fieldName, int $idformato, string $query): array
    {
        return $this->searchCiudad($query);
    }
}
