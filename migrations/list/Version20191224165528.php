<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Saia\models\Perfil;
use Doctrine\DBAL\Schema\Schema;
use Saia\Pqr\migrations\TMigrations;
use Saia\controllers\CryptController;
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

        $this->generateModules($this->modulesDefaultData());
    }

    protected function modulesDefaultData(): array
    {
        return [
            'agrupador_pqr' => [
                'pertenece_nucleo' => 0,
                'nombre' => 'agrupador_pqr',
                'tipo' => 0,
                'imagen' => 'fa fa-comments',
                'etiqueta' => 'PQRSF',
                'enlace' => '#',
                'orden' => 5,
                'color' => 'bg-danger-light',
                'children' => [
                    'configuracion_pqr' => [
                        'pertenece_nucleo' => 0,
                        'nombre' => 'configuracion_pqr',
                        'tipo' => 1,
                        'imagen' => 'fa fa-cogs',
                        'etiqueta' => 'Configuración',
                        'enlace' => NULL,
                        'orden' => 1,
                        'children' => [
                            'conf_formulario_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'conf_formulario_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-cogs',
                                'etiqueta' => 'Formulario',
                                'enlace' => 'views/modules/pqr/dist/pqr/index.html',
                                'orden' => 1
                            ]
                        ]
                    ],
                    'formulario_pqr' => [
                        'pertenece_nucleo' => 0,
                        'nombre' => 'formulario_pqr',
                        'tipo' => 1,
                        'imagen' => 'fa fa-bars',
                        'etiqueta' => 'Formularios',
                        'enlace' => NULL,
                        'orden' => 2,
                        'children' => [
                            'form_plantilla_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'form_plantilla_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-newspaper-o',
                                'etiqueta' => 'Formulario PQRSF',
                                'enlace' => 'views/modules/pqr/dist/pqr/index.html',
                                'orden' => 1
                            ],
                            'form_respuesta_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'form_respuesta_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-mail-reply',
                                'etiqueta' => 'Respuestas PQRSF',
                                'enlace' => 'views/modules/pqr/dist/respuestaPqr/index.html',
                                'orden' => 1
                            ]
                        ]
                    ],
                    'reporte_pqr' => [
                        'pertenece_nucleo' => 0,
                        'nombre' => 'reporte_pqr',
                        'tipo' => 1,
                        'imagen' => 'fa fa-bar-chart-o',
                        'etiqueta' => 'Reportes',
                        'enlace' => NULL,
                        'orden' => 3,
                        'children' => [
                            'rep_pendientes_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'rep_pendientes_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-bar-chart-o',
                                'etiqueta' => 'Pendientes',
                                'enlace' => NULL,
                                'orden' => 1
                            ],
                            'rep_proceso_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'rep_proceso_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-bar-chart-o',
                                'etiqueta' => 'En proceso',
                                'enlace' => NULL,
                                'orden' => 2
                            ],
                            'rep_terminados_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre' => 'rep_terminados_pqr',
                                'tipo' => 2,
                                'imagen' => 'fa fa-bar-chart-o',
                                'etiqueta' => 'Terminados',
                                'enlace' => NULL,
                                'orden' => 3
                            ]
                        ]
                    ],
                    'indicadores_pqr' => [
                        'pertenece_nucleo' => 0,
                        'nombre' => 'indicadores_pqr',
                        'tipo' => 1,
                        'imagen' => 'fa fa-pie-chart',
                        'etiqueta' => 'Indicadores',
                        'enlace' => 'views/modules/pqr/dist/pqr/index.html',
                        'orden' => 4,
                        'children' => []
                    ]
                ]
            ]
        ];
    }

    protected function generateModules(array $data, int $id = 0): void
    {
        if ($data) {
            foreach ($data as $name => $dataModule) {
                $child = $dataModule['children'];
                unset($dataModule['children']);

                $idmodulo = $this->createModulo(
                    array_merge($dataModule, ['cod_padre' => $id]),
                    $name
                );
                if ($child) {
                    $this->generateModules($child, $idmodulo);
                }
            }
        }
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
                'clave' => CryptController::md5Encrypt('cerok_saia'),
                'nit' => '3',
                'perfil' => Perfil::GENERAL,
                'pertenece_nucleo' => 1,
                'ventanilla_radicacion' => 0, //TODO: De donde se obtiene este campo
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
        $this->delModules($this->modulesDefaultData());
    }

    protected function delModules(array $data): void
    {
        foreach ($data as $name => $dataModule) {
            $this->deleteModulo($name);

            if ($dataModule['children']) {
                $this->delModules($dataModule['children']);
            }
        }
    }
}
