<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Exception;
use Saia\models\Cargo;
use Saia\models\Perfil;
use Saia\models\Funcionario;
use Doctrine\DBAL\Schema\Schema;
use Saia\models\DependenciaCargo;
use Saia\controllers\CriptoController;
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

        $sql = "SELECT idformato FROM formato WHERE nombre='pqr' OR nombre_tabla='ft_pqr'";
        $exist = $this->connection->fetchAll($sql);
        if ($exist) {
            $this->abortIf(true, "Ya existe el formato PQR");
        }

        $this->createRadicadorWeb();


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

    public function createRadicadorWeb(): void
    {
        $sqlCargo = "SELECT idcargo FROM cargo WHERE lower(nombre) like 'radicador web'";
        $cargo = $this->connection->fetchAll($sqlCargo);
        if (!$cargo) {
            $idcargo = Cargo::newRecord([
                'nombre' => 'Radicador Web',
                'cod_padre' => 0,
                'estado' => 1,
                'pertenece_nucleo' => 1
            ]);
        } else {
            $idcargo = $cargo[0]['idcargo'];
        }

        $sqlFuncionario = "SELECT idfuncionario FROM funcionario WHERE login='radicador_web'";
        $funcionario = $this->connection->fetchAll($sqlFuncionario);
        if (!$funcionario) {
            $idfuncionario = Funcionario::newRecord([
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
            $this->abortIf(true, "Falta el funcionario con login radicador_web");
        } else {
            $idfuncionario = $funcionario[0]['idfuncionario'];
        }

        if ($DependenciaCargo = DependenciaCargo::findByAttributes([
            'funcionario_idfuncionario' => $idfuncionario,
            'cargo_idcargo' => $idcargo
        ])) {
            $DependenciaCargo->setAttributes([
                'fecha_final' => date('Y-12-31 23:59:59'),
                'estado' => 1
            ]);
            if (!$DependenciaCargo->update()) {
                $this->abortIf(true, "No se pudo actualizar el rol del radicador_web");
            }
        } else {
            $sqlDependencia = "SELECT iddependencia FROM dependencia WHERE cod_padre=0 OR cod_padre IS NULL";
            $dependencia = $this->connection->fetchAll($sqlDependencia);
            if (!$dependencia) {
                $this->abortIf(true, "No se encuentra la dependencia principal");
            }

            if (!DependenciaCargo::newRecord([
                'funcionario_idfuncionario' => $idfuncionario,
                'cargo_idcargo' => $idcargo,
                'dependencia_iddependencia' => $dependencia[0]['iddependencia'],
                'estado' => 1,
                'fecha_ingreso' => date('Y-m-d H:i:s'),
                'fecha_inicial' => date('Y-m-d H:i:s'),
                'fecha_final' => date('Y-12-31 23:59:59')

            ])) {
                throw new Exception("No fue posible crear el rol del radicar_web", 1);
            }
        }
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

    public function createPermiso(int $idmodulo, int $idperfil): void
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

    public function deleteModulo(string $search): void
    {
        $sql = "SELECT idmodulo FROM modulo WHERE nombre like '{$search}'";
        $modulo = $this->connection->fetchAll($sql);

        if ($id = $modulo[0]['idmodulo']) {
            $this->connection->delete('modulo', ['idmodulo' => $id]);
            $this->connection->delete('permiso_perfil', ['modulo_idmodulo' => $id]);
        }
    }
}
