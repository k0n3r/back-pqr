<?php

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;

trait TMigrations
{
    protected int $idperfil = 0;
    protected int $idperfilInterno = 0;

    protected function getNameMainModule(): string
    {
        return 'agrupador_pqr';
    }

    protected function init()
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
     * @param array  $data
     * @param string $search
     * @return integer
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function createModulo(array $data, string $search): int
    {
        $sql = "SELECT idmodulo FROM modulo WHERE lower(nombre) LIKE '$search'";
        $id = (int)$this->connection->fetchOne($sql);

        if ($id) {
            $this->connection->update('modulo', $data, [
                'idmodulo' => $id
            ]);
        } else {
            $this->connection->insert('modulo', $data);
            $id = (int)$this->connection->lastInsertId('modulo');
        }

        $this->createPermiso($id, $this->idperfil);
        $this->createPermiso($id, $this->idperfilInterno);

        return $id;
    }

    protected function createPermiso(int $idmodulo, int $idperfil): void
    {
        $sql = "SELECT idpermiso_perfil FROM permiso_perfil WHERE modulo_idmodulo=$idmodulo AND perfil_idperfil=$idperfil";
        $idpermiso = (int)$this->connection->fetchOne($sql);

        if (!$idpermiso) {
            $this->connection->insert('permiso_perfil', [
                'modulo_idmodulo' => $idmodulo,
                'perfil_idperfil' => $idperfil
            ]);
        }
    }

    protected function deleteModulo(string $search): void
    {
        $sql = "SELECT idmodulo FROM modulo WHERE lower(nombre) LIKE lower('$search')";
        $id = (int)$this->connection->fetchOne($sql);

        if (!$id) {
            return;
        }

        $this->connection->delete('modulo', [
            'idmodulo' => $id
        ]);

        $this->connection->delete('permiso_perfil', [
            'modulo_idmodulo' => $id
        ]);
    }

    protected function createBusqueda(array $data, string $search): int
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) LIKE lower('$search')";
        $id = (int)$this->connection->fetchOne($sql);

        if ($id) {
            $this->connection->update('busqueda', $data, [
                'idbusqueda' => $id
            ]);
        } else {
            $this->connection->insert('busqueda', $data);
            $id = (int)$this->connection->lastInsertId('busqueda');
        }

        return $id;
    }

    protected function createBusquedaComponente(int $idbusqueda, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente
        WHERE busqueda_idbusqueda=$idbusqueda AND lower(nombre) LIKE lower('$search')";
        $id = (int)$this->connection->fetchOne($sql);

        if ($id) {
            $this->connection->update('busqueda_componente', $data, [
                'idbusqueda_componente' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_componente', $data);
            $id = (int)$this->connection->lastInsertId('busqueda_componente');
        }

        return $id;
    }

    protected function createBusquedaCondicion(int $idbusquedaComponente, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_condicion FROM busqueda_condicion
        WHERE fk_busqueda_componente=$idbusquedaComponente AND lower(etiqueta_condicion) LIKE lower('$search')";
        $id = (int)$this->connection->fetchOne($sql);

        if ($id) {
            $this->connection->update('busqueda_condicion', $data, [
                'idbusqueda_condicion' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_condicion', $data);
            $id = (int)$this->connection->lastInsertId('busqueda_condicion');
        }

        return $id;
    }

    protected function insertGraphics($graphics)
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

    protected function createGraphicSerie($data, $id)
    {
        foreach ($data as $serie) {
            $serie['fk_grafico'] = $id;
            $this->connection->insert('grafico_serie', $serie);
        }
    }

    protected function deleteBusqueda(string $search): void
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) LIKE '$search'";
        $idbusqueda = (int)$this->connection->fetchOne($sql);

        if (!$idbusqueda) {
            return;
        }

        $this->connection->delete('busqueda', [
            'idbusqueda' => $idbusqueda
        ]);

        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE busqueda_idbusqueda=$idbusqueda";
        $records = $this->connection->fetchAllAssociative($sql);

        foreach ($records as $busquedaComponente) {
            $idbusquedaComponente = $busquedaComponente['idbusqueda_componente'];
            $this->connection->delete('busqueda_componente', [
                'idbusqueda_componente' => $idbusquedaComponente
            ]);

            $this->connection->delete('busqueda_condicion', [
                'fk_busqueda_componente' => $idbusquedaComponente
            ]);
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
                'formato_idformato' => $idformato
            ]
        );

        $this->connection->delete(
            'formato',
            [
                'idformato' => $idformato
            ]
        );

        $table = "ft_$formatName";
        if ($schema->hasTable($table)) {
            $schema->dropTable($table);
        }
    }

}
