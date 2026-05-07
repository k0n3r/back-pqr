<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\JsonForIA;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Service\LegacyServiceLocator;
use Saia\controllers\generator\component\Method;
use Saia\core\model\ModelFormat;
use Saia\models\formatos\CamposFormato;

class PqrJsonForIA extends JsonForIA
{
    protected ModelFormat|FtPqr $ft;

    protected const array SPECIAL_FIELDS = [
        'sys_tercero',
        'sys_severidad',
        'sys_impacto',
        'sys_frecuencia',
        'sys_anonimo',
        PqrFormField::FIELD_NAME_SYS_DEPENDENCIA,
    ];

    protected const array EXCLUDED_FIELDS = [
        'destino_interno',
        'select_mensajeria',
        'colilla',
    ];

    protected function getexcludeHTMLTypes(): array
    {
        $data = parent::getexcludeHTMLTypes();
        $key = array_search(Method::getIdentification(), $data, true);
        if ($key !== false) {
            unset($data[$key]);
        }

        return $data;
    }

    public function getFormatFields(): array
    {
        $fields = $this->formato->getFields();

        $excludedFields = $this->getFieldsToExclude();
        $excludedHTMLTypes = $this->getexcludeHTMLTypes();

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
            $valueDB = $this->ft->$nameDb ?? '';

            $dataI = [
                'name_in_db'  => $nameDb,
                'value_in_db' => $valueDB,
                'label'       => $camposFormato->etiqueta,
                'value'       => $value,
            ];

            if ($this->isString($camposFormato->etiqueta_html) || $valueDB == $value) {
                unset($dataI['value_in_db']);
            }
            $data[] = $dataI;
        }

        return $data;
    }

    protected function getFieldsToExclude(): array
    {
        return array_merge(
            parent::getFieldsToExclude(),
            self::EXCLUDED_FIELDS,
        );
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
            if ($this->ft->$nameDb) {
                $connection = LegacyServiceLocator::getInstance()->getConnection();
                $type = $connection->createQueryBuilder()
                    ->select('phf.type')
                    ->from('pqr_form_fields', 'pff')
                    ->join('pff', 'pqr_html_fields', 'phf', 'phf.id = pff.fk_pqr_html_field')
                    ->where('pff.fk_campos_formato = :fk')
                    ->setParameter('fk', $camposFormato->getPK())
                    ->executeQuery()
                    ->fetchOne();

                if ($type === PqrHtmlField::TYPE_DEPENDENCIA) {
                    $text = $connection->createQueryBuilder()
                        ->select('nombre')
                        ->from('dependencia')
                        ->where('iddependencia = :id')
                        ->setParameter('id', $this->ft->$nameDb)
                        ->executeQuery()
                        ->fetchOne();

                    return $text ?: '';
                }

                if ($type === PqrHtmlField::TYPE_LOCALIDAD) {
                    $text = $connection->createQueryBuilder()
                        ->select("CONCAT(a.nombre, ' - ', b.nombre, ' - ', c.nombre)")
                        ->from('municipio', 'a')
                        ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
                        ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais')
                        ->where('a.idmunicipio = :id')
                        ->setParameter('id', $this->ft->$nameDb)
                        ->executeQuery()
                        ->fetchOne();

                    return $text ?: '';
                }
            }

            return '';
        }

        return $camposFormato->getComponentBuilder()->showValue($this->ft) ?? '';
    }

    private function getSpecialFieldValue(string $nameDb): string
    {
        return match ($nameDb) {
            'sys_tercero' => $this->getTerceroValue(),
            'sys_severidad', 'sys_impacto', 'sys_frecuencia' => $this->ft->getValueLabel($nameDb) ?: '',
            PqrFormField::FIELD_NAME_SYS_DEPENDENCIA => $this->ft->getSysDependencia()?->getName() ?? '',
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
