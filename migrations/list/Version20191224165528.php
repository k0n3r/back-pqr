<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191224165528 extends AbstractMigration
{
    public $idperfil;
    public $idperfilInterno;

    public function getDescription(): string
    {
        return 'Se crea el componente de PQR';
    }

    public function up(Schema $schema): void
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

        $data =    [
            'pertenece_nucleo' => 0,
            'nombre' => 'agrupador_pqr',
            'tipo' => 0,
            'imagen' => 'fa fa-comments',
            'etiqueta' => 'PQR',
            'enlace' => '#',
            'cod_padre' => 0,
            'orden' => 5,
            'color' => 'bg-danger-light'
        ];

        $id = $this->createModulo($data, 'agrupador_pqr');


        $data2 =  [
            'pertenece_nucleo' => 0,
            'nombre' => 'formulario_pqr',
            'tipo' => 1,
            'imagen' => 'fa fa-bars',
            'etiqueta' => 'Formulario',
            'enlace' => 'views/modules/pqr/dist/index.html',
            'cod_padre' => $id,
            'orden' => 1
        ];

        $id2 = $this->createModulo($data2, 'formulario_pqr');
    }

    public function createModulo(array $data, string $search): int
    {
        $sql2 = "SELECT idmodulo FROM modulo WHERE nombre like '{$search}'";
        $modulo = $this->connection->fetchAll($sql2);
        if ($id = $modulo[0]['idmodulo']) {
            $this->connection->update(
                'modulo',
                $data,
                [
                    'idmodulo' => $id
                ]
            );
        } else {
            $this->connection->insert(
                'modulo',
                $data
            );
            $id = $this->connection->lastInsertId();
        }
        $this->createPermiso((int) $id, $this->idperfil);
        $this->createPermiso((int) $id, $this->idperfilInterno);

        return (int) $id;
    }

    public function createPermiso(int $idmodulo, int $idperfil)
    {
        $sql = "SELECT idpermiso_perfil FROM permiso_perfil WHERE modulo_idmodulo={$idmodulo} AND perfil_idperfil={$idperfil}";
        $permiso = $this->connection->fetchAll($sql);
        if (!$permiso) {
            $this->connection->insert(
                'permiso_perfil',
                [
                    'modulo_idmodulo' => $idmodulo,
                    'perfil_idperfil' => $idperfil
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->deleteModulo('formulario_pqr');
        $this->deleteModulo('agrupado_por');
    }

    public function deleteModulo(string $search)
    {
        $sql = "SELECT idmodulo FROM modulo WHERE nombre like '{$search}'";
        $modulo = $this->connection->fetchAll($sql);

        if ($id = $modulo[0]['idmodulo']) {
            $this->connection->delete('modulo', ['idmodulo' => $id]);
            $this->connection->delete('permiso_perfil', ['modulo_idmodulo' => $id]);
        }
    }
}
