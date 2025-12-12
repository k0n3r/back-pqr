<?php

namespace App\Bundles\pqr\Service;

use App\Bundles\IA\Services\JsonForIA;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\component\Method;
use Saia\core\model\ModelFormat;
use Saia\models\formatos\CamposFormato;

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
        'sys_dependencia',
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

    protected function getexcludeHTMLTypes(): array
    {
        $data = parent::getexcludeHTMLTypes();
        $key = array_search(Method::getIdentification(), $data, true);
        if ($key !== false) {
            unset($data[$key]);
        }

        return $data;
    }

    protected function getFormatFields(): array
    {
        $fields = $this->formato->getFields();

        $excludedFields = array_merge(
            $this->formato->getSystemFields(),
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

    private function resolveFieldValue(string $nameDb, CamposFormato $camposFormato): string
    {
        if (in_array($nameDb, self::SPECIAL_FIELDS, true)) {
            return $this->getSpecialFieldValue($nameDb);
        }

        if ($camposFormato->valor == '{*autocompleteM*}') {
            $value = null;
            if ($this->ft->$nameDb) {
                $PqrFormField = PqrFormField::findByAttributes([
                    'fk_campos_formato' => $camposFormato->getPK(),
                ]);
                $value = $PqrFormField->getService()->getListDataForAutocomplete(
                    ['id' => $this->ft->$nameDb],
                );
            }

            return $value ? $value[0]['text'] : '';
        }

        return $camposFormato->getComponentBuilder()->showValue($this->ft) ?? '';
    }

    private function getSpecialFieldValue(string $nameDb): string
    {
        return match ($nameDb) {
            'sys_tercero' => $this->getTerceroValue(),
            'sys_severidad', 'sys_impacto', 'sys_frecuencia' => $this->ft->getValueLabel($nameDb) ?: '',
            'sys_dependencia' => $this->ft->getSysDependencia()?->getName() ?? '',
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

    protected function getSecurityAccessDepartments(): array
    {
        $dataParent = parent::getSecurityAccessDepartments();
        $dataParent[] = $this->ft->getSysDependencia()?->getName() ?? '';

        return $dataParent;
    }
}
