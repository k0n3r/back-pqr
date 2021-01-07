<?php

namespace App\Bundles\pqr\Services;

use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\models\grafico\PantallaGrafico;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrHtmlField;

class PqrService
{
    private string $errorMessage;

    /**
     * Retorna el mensaje de error
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
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
