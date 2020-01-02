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
    public function getDescription(): string
    {
        return 'Se crea el componente de PQR';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT idperfil FROM perfil WHERE lower(nombre) like '%administrador%'";
        $perfil = $this->connection->fetchAll($sql);
        $this->abortIf(!$perfil[0]['idperfil'], "No se encontro el perfil del administador");

        $this->connection->insert(
            'modulo',
            [
                'pertenece_nucleo' => 0,
                'nombre' => 'agrupador_pqr',
                'tipo' => 0,
                'imagen' => 'fa fa-comments',
                'etiqueta' => 'PQR',
                'enlace' => '#',
                'cod_padre' => 0,
                'orden' => 5
            ]
        );
        $id = $this->connection->lastInsertId();

        $this->connection->insert(
            'permiso_perfil',
            [
                'modulo_idmodulo' => $id,
                'perfil_idperfil' => $perfil[0]['idperfil']
            ]
        );

        $this->connection->insert(
            'modulo',
            [
                'pertenece_nucleo' => 0,
                'nombre' => 'formulario_pqr',
                'tipo' => 1,
                'imagen' => 'pg pg-form',
                'etiqueta' => 'Formulario',
                'enlace' => '#',
                'cod_padre' => $id,
                'orden' => 1
            ]
        );
        $idform = $this->connection->lastInsertId();

        $this->connection->insert(
            'permiso_perfil',
            [
                'modulo_idmodulo' => $idform,
                'perfil_idperfil' => $perfil[0]['idperfil']
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $sql = "SELECT idmodulo FROM modulo WHERE nombre like 'agrupador_pqr'";
        $modulo = $this->connection->fetchAll($sql);

        if ($modulo[0]['idmodulo']) {
            $this->connection->delete('modulo', ['idmodulo' => $modulo[0]['idmodulo']]);
            $this->connection->delete('permiso_perfil', ['modulo_idmodulo' => $modulo[0]['idmodulo']]);
        }
    }
}
