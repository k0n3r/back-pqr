<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Saia\core\db\customDrivers\OtherQueriesForPlatform;
use Saia\models\Perfil;
use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
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

        $this->createIndicators();
    }

    protected function modulesDefaultData(): array
    {
        return [
            'agrupador_pqr' => [
                'pertenece_nucleo' => 0,
                'nombre'           => 'agrupador_pqr',
                'tipo'             => 0,
                'imagen'           => 'fa fa-comments',
                'etiqueta'         => 'PQRSF',
                'enlace'           => '#',
                'orden'            => 5,
                'color'            => 'bg-danger-light',
                'tiene_hijos'      => 0,
                'children'         => [
                    'configuracion_pqr' => [
                        'pertenece_nucleo' => 0,
                        'nombre'           => 'configuracion_pqr',
                        'tipo'             => 1,
                        'imagen'           => 'fa fa-cogs',
                        'etiqueta'         => 'Configuración',
                        'enlace'           => null,
                        'orden'            => 1,
                        'tiene_hijos'      => 0,
                        'children'         => [
                            'conf_plantilla_pqr'  => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'conf_plantilla_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-newspaper-o',
                                'etiqueta'         => 'Formulario PQRSF',
                                'enlace'           => 'views/modules/pqr/dist/pqr/index.html',
                                'orden'            => 1,
                                'tiene_hijos'      => 0,
                            ],
                            'conf_respuesta_pqr'  => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'conf_respuesta_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-mail-reply',
                                'etiqueta'         => 'Respuestas PQRSF',
                                'enlace'           => 'views/modules/pqr/dist/respuestaPqr/index.html',
                                'orden'            => 1,
                                'tiene_hijos'      => 0
                            ],
                            'conf_formulario_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'conf_formulario_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-cogs',
                                'etiqueta'         => 'General',
                                'enlace'           => 'views/modules/pqr/dist/configuracionPqr/index.html',
                                'orden'            => 1,
                                'tiene_hijos'      => 0,
                            ]
                        ]
                    ],
                    'reporte_pqr'       => [
                        'pertenece_nucleo' => 0,
                        'nombre'           => 'reporte_pqr',
                        'tipo'             => 1,
                        'imagen'           => 'fa fa-bar-chart-o',
                        'etiqueta'         => 'Reportes',
                        'enlace'           => null,
                        'orden'            => 3,
                        'tiene_hijos'      => 0,
                        'children'         => [
                            'rep_pendientes_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'rep_pendientes_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-bar-chart-o',
                                'etiqueta'         => 'Pendientes',
                                'enlace'           => null,
                                'orden'            => 1,
                                'tiene_hijos'      => 0,
                            ],
                            'rep_proceso_pqr'    => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'rep_proceso_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-bar-chart-o',
                                'etiqueta'         => 'En proceso',
                                'enlace'           => null,
                                'orden'            => 2,
                                'tiene_hijos'      => 0,
                            ],
                            'rep_terminados_pqr' => [
                                'pertenece_nucleo' => 0,
                                'nombre'           => 'rep_terminados_pqr',
                                'tipo'             => 2,
                                'imagen'           => 'fa fa-bar-chart-o',
                                'etiqueta'         => 'Terminados',
                                'enlace'           => null,
                                'orden'            => 3,
                                'tiene_hijos'      => 0,
                            ]
                        ]
                    ],
                    'indicadores_pqr'   => [
                        'pertenece_nucleo' => 0,
                        'nombre'           => 'indicadores_pqr',
                        'tipo'             => 2,
                        'imagen'           => 'fa fa-pie-chart',
                        'etiqueta'         => 'Indicadores',
                        'enlace'           => null,
                        'orden'            => 4,
                        'tiene_hijos'      => 0,
                        'children'         => []
                    ]
                ]
            ]
        ];
    }

    protected function generateModules(array $data, int $id = 0): void
    {
        if ($data) {
            foreach ($data as $name => $dataModule) {
                if (isset($dataModule['children'])) {
                    $child = $dataModule['children'];
                    unset($dataModule['children']);
                }

                $idmodulo = $this->createModulo(
                    array_merge($dataModule, ['cod_padre' => $id]),
                    $name
                );
                if (isset($child)) {
                    $this->generateModules($child, $idmodulo);
                }
            }
        }
    }

    protected function createIndicators()
    {

        $this->connection->insert('pantalla_grafico', [
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ]);

        $id = (new OtherQueriesForPlatform($this->connection))->lastInsertId('pantalla_grafico');

        $this->createGraphic($id);
    }

    protected function createGraphic($id)
    {
        $graphics = [
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => 'Dependencia',
                'tipo'                   => '1',
                'configuracion'          => null,
                'estado'                 => 0,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Dependencia',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => null,
                'librerias'              => null,
                'titulo'                 => 'Estados por dependencia',
                'children'               => [
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT d.nombre,count(sys_dependencia) AS cantidad FROM vpqr v,dependencia d WHERE v.sys_dependencia=d.iddependencia GROUP BY sys_dependencia',
                        'etiqueta'   => 'Total'
                    ],
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT d.nombre,count(sys_dependencia) AS cantidad FROM vpqr v,dependencia d WHERE v.sys_dependencia=d.iddependencia AND sys_estado=\'PENDIENTE\' GROUP BY sys_dependencia',
                        'etiqueta'   => 'Pendiente'
                    ],
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT d.nombre,count(sys_dependencia) AS cantidad FROM vpqr v,dependencia d WHERE v.sys_dependencia=d.iddependencia AND sys_estado=\'PROCESO\' GROUP BY sys_dependencia',
                        'etiqueta'   => 'Proceso'
                    ],
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT d.nombre,count(sys_dependencia) AS cantidad FROM vpqr v,dependencia d WHERE v.sys_dependencia=d.iddependencia AND sys_estado=\'TERMINADO\' GROUP BY sys_dependencia',
                        'etiqueta'   => 'Terminado'
                    ]
                ]
            ],
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => 'Tipo',
                'tipo'                   => '2',
                'configuracion'          => null,
                'estado'                 => 0,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Tipo',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => null,
                'librerias'              => null,
                'titulo'                 => 'Tipos',
                'children'               => [
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr v,campo_opciones c WHERE v.sys_tipo=c.idcampo_opciones GROUP BY c.valor',
                        'etiqueta'   => 'Tipo'
                    ]
                ]
            ],
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => 'Estado',
                'tipo'                   => '2',
                'configuracion'          => null,
                'estado'                 => 0,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Estado',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => null,
                'librerias'              => null,
                'titulo'                 => 'Estados',
                'children'               => [
                    [
                        'fk_grafico' => '6',
                        'query'      => 'SELECT sys_estado,count(sys_estado) AS cantidad FROM vpqr GROUP BY sys_estado',
                        'etiqueta'   => 'Estados'
                    ]
                ]
            ]
        ];

        foreach ($graphics as $graphic) {

            $graphicSerie = $graphic['children'];
            unset($graphic['children']);

            $this->connection->insert('grafico', $graphic);

            if ($graphicSerie) {
                $id = (new OtherQueriesForPlatform($this->connection))->lastInsertId('grafico');
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

    protected function validateCreation(): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre='pqr' OR nombre_tabla='ft_pqr'";
        $exist = $this->connection->fetchAllAssociative($sql);
        if ($exist) {
            $this->abortIf(true, "Ya existe el formato PQR");
        }

        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre='reporte_pqr'";
        $exist = $this->connection->fetchAllAssociative($sql);
        if ($exist) {
            $this->abortIf(true, "Ya existe un reporte de PQR");
        }
    }

    protected function createRadicadorWeb(): void
    {
        $sqlCargo = "SELECT idcargo FROM cargo WHERE lower(nombre) like 'radicador web'";
        $cargo = $this->connection->fetchAllAssociative($sqlCargo);
        if (!$cargo) {
            $this->connection->insert('cargo', [
                'nombre'           => 'Radicador Web',
                'estado'           => 1,
                'pertenece_nucleo' => 1
            ]);
            $idcargo = (new OtherQueriesForPlatform($this->connection))->lastInsertId('cargo');
        } else {
            $idcargo = $cargo[0]['idcargo'];
        }

        if (!$idcargo) {
            $this->abortIf(true, "No fue posible encontrar el cargo Radicador Web");
        }

        $sqlFuncionario = "SELECT idfuncionario FROM funcionario WHERE login='radicador_web'";
        $funcionario = $this->connection->fetchAllAssociative($sqlFuncionario);
        if (!$funcionario) {
            $this->connection->insert('funcionario', [
                'login'                 => 'radicador_web',
                'nombres'               => 'Ventanilla',
                'apellidos'             => 'Web',
                'estado'                => 1,
                'fecha_ingreso'         => date('Y-m-d H:i:s'),
                'clave'                 => CryptController::encrypt('cerok_saia'),
                'nit'                   => '3',
                'perfil'                => Perfil::GENERAL,
                'pertenece_nucleo'      => 1,
                'ventanilla_radicacion' => 0, //TODO: De donde se obtiene este campo
            ]);
            $idfuncionario = (new OtherQueriesForPlatform($this->connection))->lastInsertId('funcionario');
        } else {
            $idfuncionario = $funcionario[0]['idfuncionario'];
        }
        if (!$idfuncionario) {
            $this->abortIf(true, "No fue posible encontrar el funcionario Radicador Web");
        }

        $sqlDependenciaCargo = "SELECT iddependencia_cargo FROM dependencia_cargo 
        WHERE funcionario_idfuncionario=$idfuncionario AND cargo_idcargo=$idcargo";
        $dependenciaCargo = $this->connection->fetchAllAssociative($sqlDependenciaCargo);

        if ($dependenciaCargo) {
            $this->connection->update('dependencia_cargo', [
                'fecha_final' => date('Y-12-31 23:59:59'),
                'estado'      => 1
            ], [
                'iddependencia_cargo' => $dependenciaCargo[0]['iddependencia_cargo']
            ]);
        } else {
            $sqlDependencia = "SELECT iddependencia FROM dependencia WHERE cod_padre=0 OR cod_padre IS NULL";
            $dependencia = $this->connection->fetchAllAssociative($sqlDependencia);
            if (!$dependencia) {
                $this->abortIf(true, "No se encuentra la dependencia principal");
            }

            $this->connection->insert('dependencia_cargo', [
                'funcionario_idfuncionario' => $idfuncionario,
                'cargo_idcargo'             => $idcargo,
                'dependencia_iddependencia' => $dependencia[0]['iddependencia'],
                'estado'                    => 1,
                'fecha_ingreso'             => date('Y-m-d H:i:s'),
                'fecha_inicial'             => date('Y-m-d H:i:s'),
                'fecha_final'               => date('Y-12-31 23:59:59')
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->delModules($this->modulesDefaultData());
        $this->delGraphic();

        //ELIMINAR DATOS DE PQR
        $this->delOtherModules();
        $this->deleteFormat('pqr', $schema);

        $sql = $this->connection->getDatabasePlatform()->getDropViewSQL('vpqr');
        $this->connection->executeStatement($sql);

        $sql = $this->connection->getDatabasePlatform()->getDropViewSQL('vpqr_respuesta');
        $this->connection->executeStatement($sql);

        $sql = $this->connection->getDatabasePlatform()->getDropViewSQL('vpqr_calificacion');
        $this->connection->executeStatement($sql);
    }

    protected function delOtherModules()
    {
        $nameModules = [
            'crear_pqr',
            'crear_pqr_respuesta',
            'crear_pqr_calificacion'
        ];
        foreach ($nameModules as $name) {
            $this->deleteModulo($name);
        }
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

    protected function delGraphic()
    {
        $screen = [
            PqrForm::NOMBRE_PANTALLA_GRAFICO
        ];

        foreach ($screen as $name) {
            $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE nombre='$name'";
            $data = $this->connection->fetchOne($sql);

            if ($data['idpantalla_grafico']) {
                $this->connection->delete('pantalla_grafico', [
                    'idpantalla_grafico' => $data['idpantalla_grafico']
                ]);

                $sql = "SELECT idgrafico FROM grafico WHERE fk_pantalla_grafico='{$data['idpantalla_grafico']}'";
                $records = $this->connection->fetchAllAssociative($sql);

                foreach ($records as $graphic) {
                    $this->connection->delete('grafico_serie', [
                        'fk_grafico' => $graphic['idgrafico']
                    ]);
                }

                $this->connection->delete('grafico', [
                    'fk_pantalla_grafico' => $data['idpantalla_grafico']
                ]);
            }
        }
    }
}
