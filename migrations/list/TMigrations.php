<?php

namespace Saia\Pqr\Migrations;

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
        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) like '%administrador%'";
        $perfil = $this->connection->fetchAll($sql);
        if ($perfil) {
            $this->idperfil = (int) $perfil[0]['idperfil'];
        } else {
            $this->abortIf(true, "No se encontro el perfil del administador");
        }

        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) like '%admin_interno%'";
        $perfil2 = $this->connection->fetchAll($sql);
        if ($perfil2) {
            $this->idperfilInterno = (int) $perfil2[0]['idperfil'];
        } else {
            $this->abortIf(true, "No se encontro el perfil del administador interno");
        }
    }

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
}
