<?php

namespace App\Bundles\pqr\Services;

use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\models\grafico\PantallaGrafico;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;

class PqrService
{

    /**
     * Obtiene los campos que se podran utilizar para la
     * carga automatica del destino de la respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function getTextFields(): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('ff.*')
            ->from('pqr_form_fields', 'ff')
            ->join('ff', 'pqr_html_fields', 'hf', 'ff.fk_pqr_html_field=hf.id')
            ->where("hf.type_saia='Text' and ff.active=1")
            ->orderBy('ff.orden');

        $data = [];
        if ($records = PqrFormField::findByQueryBuilder($Qb)) {
            foreach ($records as $PqrFormField) {
                $data[] = [
                    'id' => $PqrFormField->getPK(),
                    'text' => $PqrFormField->label
                ];
            }
        }

        return $data;
    }

    /**
     * Obtiene los componentes para creacion del formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     */
    public static function getDataHtmlFields(): array
    {
        $data = [];

        if ($records = PqrHtmlField::findAllByAttributes([
            'active' => 1
        ])) {
            foreach ($records as $PqrHtmlField) {
                $data[] = $PqrHtmlField->getDataAttributes();
            }
        }

        return $data;
    }

    /**
     * Activa los indicadores preestablecidos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    public static function activeGraphics(): void
    {
        if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ])) {
            throw new \Exception("No se encuentra la pantalla de los grafico", 200);
        }

        DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->update('grafico')
            ->set('estado', 1)
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter(':idpantalla', $PantallaGrafico->getPK(), Type::getType('integer'))
            ->andWhere("nombre<>'Dependencia'")
            ->execute();
    }
}
