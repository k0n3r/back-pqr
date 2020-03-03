<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Saia\models\Perfil;
use Doctrine\DBAL\Schema\Schema;
use Saia\Pqr\Migrations\TMigrations;
use Saia\controllers\CriptoController;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191224165528 extends AbstractMigration
{
    use TMigrations;

    public function getDescription(): string
    {
        return 'Se crea el componente de PQR';
    }

    public function up(Schema $schema): void
    {
        $this->init();
        $this->validateCreation();
        $this->createRadicadorWeb();

        $data = [
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

        $id = $this->createModulo($data, $this->getNameMainModule());


        $data2 =  [
            'pertenece_nucleo' => 0,
            'nombre' => 'formulario_pqr',
            'tipo' => 1,
            'imagen' => 'fa fa-bars',
            'etiqueta' => 'Formularios',
            'enlace' => NULL,
            'cod_padre' => $id,
            'orden' => 1
        ];

        $id = $this->createModulo($data2, 'formulario_pqr');

        $data2 =  [
            'pertenece_nucleo' => 0,
            'nombre' => 'plantilla_pqr',
            'tipo' => 1,
            'imagen' => 'fa fa-newspaper-o',
            'etiqueta' => 'Formulario PQR',
            'enlace' => 'views/modules/pqr/dist/index.html',
            'cod_padre' => $id,
            'orden' => 1
        ];

        $this->createModulo($data2, 'plantilla_pqr');

        $data3 =  [
            'pertenece_nucleo' => 0,
            'nombre' => 'respuesta_pqr',
            'tipo' => 1,
            'imagen' => 'fa fa-mail-reply',
            'etiqueta' => 'Respuesta PQR',
            'enlace' => 'views/modules/pqr/dist/index.html',
            'cod_padre' => $id,
            'orden' => 1
        ];

        $this->createModulo($data3, 'respuesta_pqr');
    }

    protected function validateCreation(): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre='pqr' OR nombre_tabla='ft_pqr'";
        $exist = $this->connection->fetchAll($sql);
        if ($exist) {
            $this->abortIf(true, "Ya existe el formato PQR");
        }

        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre='reporte_pqr'";
        $exist = $this->connection->fetchAll($sql);
        if ($exist) {
            $this->abortIf(true, "Ya existe un reporte de PQR");
        }
    }

    protected function createRadicadorWeb(): void
    {
        $sqlCargo = "SELECT idcargo FROM cargo WHERE lower(nombre) like 'radicador web'";
        $cargo = $this->connection->fetchAll($sqlCargo);
        if (!$cargo) {
            $this->connection->insert('cargo', [
                'nombre' => 'Radicador Web',
                'cod_padre' => 0,
                'estado' => 1,
                'pertenece_nucleo' => 1
            ]);
            $idcargo = $this->connection->lastInsertId();
        } else {
            $idcargo = $cargo[0]['idcargo'];
        }

        if (!$idcargo) {
            $this->abortIf(true, "No fue posible encontrar el cargo Radicador Web");
        }

        $sqlFuncionario = "SELECT idfuncionario FROM funcionario WHERE login='radicador_web'";
        $funcionario = $this->connection->fetchAll($sqlFuncionario);
        if (!$funcionario) {
            $this->connection->insert('funcionario', [
                'login' => 'radicador_web',
                'nombres' => 'Ventanilla',
                'apellidos' => 'Web',
                'estado' => 1,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'clave' => CriptoController::encrypt_md5('cerok_saia'),
                'nit' => '3',
                'perfil' => Perfil::GENERAL,
                'pertenece_nucleo' => 1,
                'sistema' => 1, //TODO: Preguntar si se puede borrar el campo
                'ventanilla_radicacion' => 1, //TODO: De donde se obtiene este campo
            ]);
            $idfuncionario = $this->connection->lastInsertId();
        } else {
            $idfuncionario = $funcionario[0]['idfuncionario'];
        }
        if (!$idfuncionario) {
            $this->abortIf(true, "No fue posible encontrar el funcionario Radicador Web");
        }

        $sqlDependenciaCargo = "SELECT iddependencia_cargo FROM dependencia_cargo 
        WHERE funcionario_idfuncionario={$idfuncionario} AND cargo_idcargo={$idcargo}";
        $dependenciaCargo = $this->connection->fetchAll($sqlDependenciaCargo);

        if ($dependenciaCargo[0]['iddependencia_cargo']) {
            $this->connection->update('dependencia_cargo', [
                'fecha_final' => date('Y-12-31 23:59:59'),
                'estado' => 1
            ], [
                'iddependencia_cargo' => $dependenciaCargo[0]['iddependencia_cargo']
            ]);
        } else {
            $sqlDependencia = "SELECT iddependencia FROM dependencia WHERE cod_padre=0 OR cod_padre IS NULL";
            $dependencia = $this->connection->fetchAll($sqlDependencia);
            if (!$dependencia) {
                $this->abortIf(true, "No se encuentra la dependencia principal");
            }

            $this->connection->insert('dependencia_cargo', [
                'funcionario_idfuncionario' => $idfuncionario,
                'cargo_idcargo' => $idcargo,
                'dependencia_iddependencia' => $dependencia[0]['iddependencia'],
                'estado' => 1,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'fecha_inicial' => date('Y-m-d H:i:s'),
                'fecha_final' => date('Y-12-31 23:59:59')
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->deleteModulo('formulario_pqr');
        $this->deleteModulo('agrupador_pqr');
    }
}
