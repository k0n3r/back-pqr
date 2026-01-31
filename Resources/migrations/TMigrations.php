<?php

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use DoctrineMigrations\UtilsMigrationsTrait;

trait TMigrations
{
    use UtilsMigrationsTrait {
        UtilsMigrationsTrait::createModulo as traitCreateModulo;
    }

    protected int $idperfil = 0;
    protected int $idperfilInterno = 0;

    protected function getNameMainModule(): string
    {
        return 'agrupador_pqr';
    }

    protected function init(): void
    {
        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) LIKE 'administrador'";
        $this->idperfil = (int)$this->connection->fetchOne($sql);
        $this->abortIf(!$this->idperfil, "No se encontro el perfil del administador");

        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) LIKE 'administrador interno'";
        $this->idperfilInterno = (int)$this->connection->fetchOne($sql);
        $this->abortIf(!$this->idperfilInterno, "No se encontro el perfil del administador interno");
    }

    /**
     * Crea o actualiza un modulo
     *
     * @param array $data
     * @param string $search
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function createModulo(array $data, string $search): int
    {
        $id = $this->traitCreateModulo($data, $search);

        $this->createPermiso($id, $this->idperfil);
        $this->createPermiso($id, $this->idperfilInterno);

        return $id;
    }


    protected function insertGraphics($graphics): void
    {
        foreach ($graphics as $graphic) {
            $graphicSerie = $graphic['children'];
            unset($graphic['children']);

            $this->connection->insert('grafico', $graphic);

            if ($graphicSerie) {
                $id = $this->connection->lastInsertId('grafico');
                $this->createGraphicSerie($graphicSerie, $id);
            }
        }
    }

    protected function createGraphicSerie($data, $id): void
    {
        foreach ($data as $serie) {
            $serie['fk_grafico'] = $id;
            $this->connection->insert('grafico_serie', $serie);
        }
    }

    protected function deleteFormat(string $formatName, Schema $schema): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre LIKE '$formatName'";
        $idformato = (int)$this->connection->fetchOne($sql);
        $this->abortIf(!$idformato, "No se encontro el formato $formatName");

        $this->connection->delete(
            'campos_formato',
            [
                'formato_idformato' => $idformato,
            ],
        );

        $this->connection->delete(
            'formato',
            [
                'idformato' => $idformato,
            ],
        );

        $table = "ft_$formatName";
        if ($schema->hasTable($table)) {
            $schema->dropTable($table);
        }
    }

}
