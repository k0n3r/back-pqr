<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214144616 extends AbstractMigration
{
    use TDependencyReport;

    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Crea reportes y graficos de Dependencias';
    }

    public function up(Schema $schema): void
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA;

        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre LIKE '$nombreComponente'";
        $exist = $this->connection->fetchOne($sql);

        if ($exist) {
            return;
        }

        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre LIKE 'reporte_pqr'";
        $idbusqueda = (int)$this->connection->fetchOne($sql);

        $this->createComponentePorDependencia($idbusqueda);
        $this->createGraphicCalificacion();
        $this->updateReportPqr();
    }

    private function createGraphicCalificacion()
    {
        $nombre = PqrForm::NOMBRE_PANTALLA_GRAFICO;
        $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE nombre LIKE '$nombre'";
        $id = $this->connection->fetchOne($sql);

        $graphics = [
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
                        'query'      => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_gestion=c.idcampo_opciones GROUP BY c.valor',
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
                        'query'      => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_servicio=c.idcampo_opciones GROUP BY c.valor',
                        'etiqueta'   => 'Calificación',
                    ]
                ]
            ]
        ];

        $this->insertGraphics($graphics);
    }

    private function updateReportPqr()
    {

        $this->connection->update('busqueda_componente', [
            'ruta_libreria' => "src/Bundles/pqr/formatos/pqr/reporteFunciones.php",
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ]);

        $this->connection->update('busqueda_componente', [
            'ruta_libreria' => "src/Bundles/pqr/formatos/pqr/reporteFunciones.php",
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ]);

        $this->connection->update('busqueda_componente', [
            'ruta_libreria' => "src/Bundles/pqr/formatos/pqr/reporteFunciones.php",
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ]);

        $this->connection->update('busqueda_componente', [
            'ruta_libreria' => "src/Bundles/pqr/formatos/pqr/reporteFunciones.php",
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ]);

        //--------------

        $this->connection->update('busqueda_condicion', [
            'codigo_where' => "{*filter_pqr*}",
        ], [
            'etiqueta_condicion' => PqrForm::NOMBRE_REPORTE_TODOS
        ]);

    }

    public function down(Schema $schema): void
    {
        $names = [
            PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA
        ];

        foreach ($names as $nombreComponente) {
            $this->connection->delete('busqueda_componente', [
                'nombre' => $nombreComponente
            ]);
            $this->connection->delete('busqueda_condicion', [
                'etiqueta_condicion' => $nombreComponente
            ]);
        }


        $graphics = [
            'pqr_calificacion_gestion',
            'pqr_calificacion_servicio'
        ];

        foreach ($graphics as $graphicName) {
            $sql = "SELECT idgrafico FROM grafico WHERE nombre LIKE '$graphicName'";
            $id = $this->connection->fetchOne($sql);

            $sql = "SELECT idgrafico_serie FROM grafico_serie WHERE fk_grafico =$id";
            $records = $this->connection->fetchAllAssociative($sql);

            foreach ($records as $idGraphicSerie) {
                $this->connection->delete('grafico_serie', [
                    'idgrafico_serie' => $idGraphicSerie
                ]);
            }

            $this->connection->delete('grafico', [
                'idgrafico' => $id
            ]);
        }

    }
}
