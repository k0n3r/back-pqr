<?php

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;

trait TMigrations
{
    protected $idperfil;
    protected $idperfilInterno;

    protected function getNameMainModule()
    {
        return 'agrupador_pqr';
    }

    protected function init()
    {
        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) like 'ADMINISTRADOR'";
        $perfil = $this->connection->fetchAll($sql);
        if ($perfil) {
            $this->idperfil = (int) $perfil[0]['idperfil'];
        } else {
            $this->abortIf(true, "No se encontro el perfil del administador");
        }

        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) like 'ADMINISTRADOR INTERNO'";
        $perfil2 = $this->connection->fetchAll($sql);
        if ($perfil2) {
            $this->idperfilInterno = (int) $perfil2[0]['idperfil'];
        } else {
            $this->abortIf(true, "No se encontro el perfil del administador interno");
        }
    }

    /**
     * Crea o actualiza un modulo
     *
     * @param array $data
     * @param string $search
     * @return integer
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createModulo(array $data, string $search): int
    {
        $sql = "SELECT idmodulo FROM modulo WHERE lower(nombre) like '{$search}'";
        $modulo = $this->connection->fetchAll($sql);

        if ($modulo[0]['idmodulo']) {
            $id = $modulo[0]['idmodulo'];
            $this->connection->update('modulo', $data, [
                'idmodulo' => $id
            ]);
        } else {
            $this->connection->insert('modulo', $data);
            $id = $this->connection->lastInsertId();
        }

        $this->createPermiso((int) $id, $this->idperfil);
        $this->createPermiso((int) $id, $this->idperfilInterno);

        return (int) $id;
    }

    protected function createPermiso(int $idmodulo, int $idperfil): void
    {
        $sql = "SELECT idpermiso_perfil FROM permiso_perfil WHERE modulo_idmodulo={$idmodulo} AND perfil_idperfil={$idperfil}";
        $permiso = $this->connection->fetchAll($sql);

        if (!$permiso[0]['idpermiso_perfil']) {
            $this->connection->insert('permiso_perfil', [
                'modulo_idmodulo' => $idmodulo,
                'perfil_idperfil' => $idperfil
            ]);
        }
    }

    protected function deleteModulo(string $search): void
    {
        $sql = "SELECT idmodulo FROM modulo WHERE lower(nombre) like '{$search}'";
        $modulo = $this->connection->fetchAll($sql);

        if ($modulo[0]['idmodulo']) {
            $id = $modulo[0]['idmodulo'];
            $this->connection->delete('modulo', [
                'idmodulo' => $id
            ]);

            $this->connection->delete('permiso_perfil', [
                'modulo_idmodulo' => $id
            ]);
        }
    }

    protected function createBusqueda(array $data, string $search): int
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda']) {
            $id = $record[0]['idbusqueda'];
            $this->connection->update('busqueda', $data, [
                'idbusqueda' => $id
            ]);
        } else {
            $this->connection->insert('busqueda', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    protected function createBusquedaComponente(int $idbusqueda, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente
        WHERE busqueda_idbusqueda={$idbusqueda} AND lower(nombre) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda_componente']) {
            $id = $record[0]['idbusqueda_componente'];

            $this->connection->update('busqueda_componente', $data, [
                'idbusqueda_componente' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_componente', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    protected function createBusquedaCondicion(int $idbusquedaComponente, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_condicion FROM busqueda_condicion
        WHERE fk_busqueda_componente={$idbusquedaComponente} AND lower(etiqueta_condicion) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda_condicion']) {
            $id = $record[0]['idbusqueda_condicion'];
            $this->connection->update('busqueda_condicion', $data, [
                'idbusqueda_condicion' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_condicion', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    protected function deleteBusqueda(string $search): void
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) like '{$search}'";
        $busqueda = $this->connection->fetchAll($sql);

        if ($busqueda[0]['idbusqueda']) {
            $idbusqueda = $busqueda[0]['idbusqueda'];
            $this->connection->delete('busqueda', [
                'idbusqueda' => $idbusqueda
            ]);

            $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE busqueda_idbusqueda={$idbusqueda}";
            $records = $this->connection->fetchAll($sql);

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
    }

    protected function deleteFormat(string $formatName, Schema $schema)
    {

        $sql = "SELECT idformato FROM formato WHERE nombre like '{$formatName}'";
        $data = $this->connection->executeQuery($sql)->fetchAll();
        if (!$data[0]['idformato']) {
            $this->abortIf(true, "No se encontro el formato {$formatName}");
        }

        $idformato = $data[0]['idformato'];
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

        $table = "ft_{$formatName}";
        if ($schema->hasTable($table)) {
            $schema->dropTable($table);
        }
    }
}
