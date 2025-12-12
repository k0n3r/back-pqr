<?php

namespace App\Bundles\pqr\Service;

use App\Bundles\IA\Services\JsonForIA;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\core\model\ModelFormat;

class PqrDataJsonForIA extends JsonForIA
{
    protected ModelFormat|FtPqr $ft;

    protected array $otherMetadata = [];

    protected const array SPECIAL_FIELDS = [
        'sys_tercero',
        'sys_severidad',
        'sys_impacto',
        'sys_frecuencia',
        'sys_anonimo',
    ];

    protected const array METADATA_FIELD_MAP = [
        'sys_estado' => 'pqrStatus',
        'sys_tipo'   => 'pqrType',
    ];

    protected const array EXCLUDED_FIELDS = [
        'destino_interno',
        'select_mensajeria',
        'colilla',
    ];

    protected function getMetadataAttributes(): array
    {
        return array_merge(parent::getMetadataAttributes(), $this->otherMetadata);
    }

    protected function getFormatFields(): array
    {
        $fields = $this->formato->getFields();

        $excludedFields = array_merge(
            $this->formato->getSystemFields(true),
            self::EXCLUDED_FIELDS,
        );
        $excludedHTMLTypes = $this->getexcludeHTMLTypes();
        $metadataFields = array_keys(self::METADATA_FIELD_MAP);

        $data = [];

        foreach ($fields as $camposFormato) {
            $nameDb = $camposFormato->nombre;

            if ($this->shouldExcludeField(
                $nameDb,
                $camposFormato->etiqueta_html,
                $excludedFields,
                $excludedHTMLTypes,
            )) {
                continue;
            }

            $value = $this->resolveFieldValue($nameDb, $camposFormato);

            $data[] = [
                'name_in_db'  => $nameDb,
                'value_in_db' => $this->ft->$nameDb ?? '',
                'label'       => $camposFormato->etiqueta ?? '',
                'value'       => $value,
            ];

            if (in_array($nameDb, $metadataFields, true)) {
                $this->otherMetadata[self::METADATA_FIELD_MAP[$nameDb]] = $value;
            }
        }

        return $data;
    }

    private function shouldExcludeField(
        string $nameDb,
        string $htmlType,
        array $excludedFields,
        array $excludedHTMLTypes,
    ): bool {
        return in_array($nameDb, $excludedFields, true)
            || in_array($htmlType, $excludedHTMLTypes, true);
    }

    private function resolveFieldValue(string $nameDb, $camposFormato): string
    {
        if (in_array($nameDb, self::SPECIAL_FIELDS, true)) {
            return $this->getSpecialFieldValue($nameDb);
        }

        return $camposFormato->getComponentBuilder()->showValue($this->ft) ?? '';
    }

    private function getSpecialFieldValue(string $nameDb): string
    {
        return match ($nameDb) {
            'sys_tercero' => $this->getTerceroValue(),
            'sys_severidad', 'sys_impacto', 'sys_frecuencia' => $this->ft->getValueLabel($nameDb) ?: '',
            'sys_anonimo' => $this->ft->sys_anonimo ? 'SI' : 'NO',
            default => '',
        };
    }

    private function getTerceroValue(): string
    {
        $tercero = $this->ft->getTercero();
        $email = $tercero->getEmail() ?? 'N/A';

        return sprintf('%s (%s)', $tercero->getLabel(), $email);
    }
}
