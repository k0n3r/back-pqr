<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\PqrService;
use DateTime;
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

    private function convertDate(string $date): string
    {
        $DateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $DateTime->format($this->connection->getDatabasePlatform()->getDateTimeFormatString());
    }

    private function modulesDefaultData(): array
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

    private function generateModules(array $data, int $id = 0): void
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

    private function createIndicators()
    {
        $this->connection->insert('pantalla_grafico', [
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ]);
        $id = $this->connection->lastInsertId('pantalla_grafico');

        $this->createGraphic($id);
    }

    private function createGraphic($id)
    {
        $graphics = [
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => PqrService::NAME_DEPENDENCY_GRAPH,
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
                'nombre'                 => 'pqr_tipo',
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
                'nombre'                 => 'pqr_estado',
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
                        'fk_grafico' => 0,
                        'query'      => 'SELECT sys_estado,count(sys_estado) AS cantidad FROM vpqr GROUP BY sys_estado',
                        'etiqueta'   => 'Estado'
                    ]
                ]
            ],
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => 'pqr_calificacion_gestion',
                'tipo'                   => '2',
                'configuracion'          => null,
                'estado'                 => 1,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Calificación',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => null,
                'librerias'              => null,
                'titulo'                 => 'Calificación en Gestión',
                'children'               => [
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_servicio=c.idcampo_opciones GROUP BY c.valor',
                        'etiqueta'   => 'Calificación',
                    ]
                ]
            ],
            [
                'fk_busqueda_componente' => null,
                'fk_pantalla_grafico'    => $id,
                'nombre'                 => 'pqr_calificacion_servicio',
                'tipo'                   => '2',
                'configuracion'          => null,
                'estado'                 => 1,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Calificación',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => null,
                'librerias'              => null,
                'titulo'                 => 'Calificación en Servicio',
                'children'               => [
                    [
                        'fk_grafico' => 0,
                        'query'      => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_gestion=c.idcampo_opciones GROUP BY c.valor',
                        'etiqueta'   => 'Calificación',
                    ]
                ]
            ]
        ];

        $this->insertGraphics($graphics);
    }

    private function validateCreation(): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre LIKE 'pqr' OR nombre_tabla LIKE 'ft_pqr'";
        $exist = (bool)$this->connection->fetchOne($sql);
        $this->abortIf($exist, "Ya existe el formato PQR");

        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre LIKE 'reporte_pqr'";
        $exist = (bool)$this->connection->fetchOne($sql);
        $this->abortIf($exist, "Ya existe un reporte de PQR");
    }

    private function createRadicadorWeb(): void
    {
        $sqlCargo = "SELECT idcargo FROM cargo WHERE lower(nombre) like 'radicador web'";
        $idcargo = (int)$this->connection->fetchOne($sqlCargo);
        if (!$idcargo) {
            $this->connection->insert('cargo', [
                'nombre'           => 'Radicador Web',
                'estado'           => 1,
                'pertenece_nucleo' => 1
            ]);
            $idcargo = $this->connection->lastInsertId('cargo');
        }
        $this->abortIf(!$idcargo, "No fue posible encontrar el cargo Radicador Web");

        $sqlFuncionario = "SELECT idfuncionario FROM funcionario WHERE login='radicador_web'";
        $idfuncionario = (int)$this->connection->fetchOne($sqlFuncionario);
        if (!$idfuncionario) {
            $this->connection->insert('funcionario', [
                'login'                 => 'radicador_web',
                'nombres'               => 'Ventanilla',
                'apellidos'             => 'Web',
                'estado'                => 1,
                'fecha_ingreso'         => $this->convertDate(date('Y-m-d H:i:s')),
                'clave'                 => CryptController::encrypt('cerok_saia'),
                'nit'                   => '3',
                'perfil'                => Perfil::GENERAL,
                'pertenece_nucleo'      => 1,
                'ventanilla_radicacion' => 0, //TODO: De donde se obtiene este campo
            ]);
            $idfuncionario = $this->connection->lastInsertId('funcionario');
        }
        $this->abortIf(!$idfuncionario, "No fue posible encontrar el funcionario Radicador Web");


        $sqlDependenciaCargo = "SELECT iddependencia_cargo FROM dependencia_cargo 
        WHERE funcionario_idfuncionario=$idfuncionario AND cargo_idcargo=$idcargo";
        $iddependencia_cargo = (int)$this->connection->fetchOne($sqlDependenciaCargo);

        if ($iddependencia_cargo) {
            $this->connection->update('dependencia_cargo', [
                'fecha_final' => $this->convertDate(date('Y-12-31 23:59:59')),
                'estado'      => 1
            ], [
                'iddependencia_cargo' => $iddependencia_cargo
            ]);
        } else {
            $sqlDependencia = "SELECT iddependencia FROM dependencia WHERE cod_padre=0 OR cod_padre IS NULL";
            $iddependencia = (int)$this->connection->fetchOne($sqlDependencia);
            $this->abortIf(!$iddependencia, "No se encuentra la dependencia principal");

            $this->connection->insert('dependencia_cargo', [
                'funcionario_idfuncionario' => $idfuncionario,
                'cargo_idcargo'             => $idcargo,
                'dependencia_iddependencia' => $iddependencia,
                'estado'                    => 1,
                'fecha_ingreso'             => $this->convertDate(date('Y-m-d H:i:s')),
                'fecha_inicial'             => $this->convertDate(date('Y-m-d H:i:s')),
                'fecha_final'               => $this->convertDate(date('Y-12-31 23:59:59'))
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

        $OtherQueriesForPlatform = new OtherQueriesForPlatform($this->connection);
        $OtherQueriesForPlatform->dropViewIfExist('vpqr');
        $OtherQueriesForPlatform->dropViewIfExist('vpqr_respuesta');
        $OtherQueriesForPlatform->dropViewIfExist('vpqr_calificacion');
    }

    private function delOtherModules()
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

    private function delModules(array $data): void
    {
        foreach ($data as $name => $dataModule) {
            $this->deleteModulo($name);

            if (isset($dataModule['children'])) {
                $this->delModules($dataModule['children']);
            }
        }
    }

    private function delGraphic(): void
    {
        $screen = [
            PqrForm::NOMBRE_PANTALLA_GRAFICO
        ];

        foreach ($screen as $name) {
            $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE lower(nombre) LIKE lower('$name')";
            $id = (int)$this->connection->fetchOne($sql);

            if (!$id) {
                return;
            }

            $this->connection->delete('pantalla_grafico', [
                'idpantalla_grafico' => $id
            ]);

            $sql = "SELECT idgrafico FROM grafico WHERE fk_pantalla_grafico=$id";
            $records = $this->connection->fetchAllAssociative($sql);

            foreach ($records as $graphic) {
                $this->connection->delete('grafico_serie', [
                    'fk_grafico' => $graphic['idgrafico']
                ]);
            }

            $this->connection->delete('grafico', [
                'fk_pantalla_grafico' => $id
            ]);
        }

    }
}