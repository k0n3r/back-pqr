<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Saia\models\Funcionario;
use Saia\models\Modulo;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200321234633 extends AbstractMigration
{
    use TMigrations;

    private string $formatName = 'pqr_calificacion';

    public function getDescription(): string
    {
        return 'Creacion del formato calificacion de la PQR';
    }

    public function up(Schema $schema): void
    {
        $idformato = $this->createFormat();
        $this->createFields($idformato);
        $this->createModuleFormat();
    }

    private function createModuleFormat(): int
    {
        $moduleName = sprintf(
            "crear_%s",
            $this->formatName
        );
        $sql = "SELECT idmodulo FROM modulo WHERE nombre like 'modulo_formatos'";
        $idModulo = (int)$this->connection->fetchOne($sql);

        $attributes = [
            'nombre'      => $moduleName,
            'tipo'        => Modulo::TIPO_HIJO,
            'etiqueta'    => 'CALIFICACIÓN -PQRSF',
            'enlace'      => "views/modules/pqr/formatos/$this->formatName/adicionar.html",
            'cod_padre'   => $idModulo,
            'tiene_hijos' => 0
        ];

        return $this->createModulo($attributes, $moduleName);
    }

    private function createFormat(): int
    {
        $sql = "SELECT idcontador FROM contador WHERE nombre like 'apoyo'";
        $idcontador = (int)$this->connection->fetchOne($sql);
        $this->abortIf(!$idcontador, "El contador apoyo NO existe");

        $idfuncionario = Funcionario::CEROK;
        $this->abortIf(!$idfuncionario, "El funcionario SAIA NO existe");

        $sqlCodPadre = "SELECT idformato FROM formato WHERE nombre like 'pqr_respuesta'";
        $codPadre = (int)$this->connection->fetchOne($sqlCodPadre);
        $this->abortIf(!$codPadre, "No se encontro el formato padre Respuesta PQRSF");

        $name = $this->formatName;
        $data = [
            'nombre'                    => $name,
            'etiqueta'                  => 'CALIFICACIÓN -PQRSF',
            'cod_padre'                 => $codPadre,
            'contador_idcontador'       => $idcontador,
            'nombre_tabla'              => "ft_$name",
            'ruta_mostrar'              => "views/modules/pqr/formatos/$name/mostrar.php",
            'ruta_editar'               => "views/modules/pqr/formatos/$name/editar.html",
            'ruta_adicionar'            => "views/modules/pqr/formatos/$name/adicionar.html",
            'ruta_buscar'               => "views/modules/pqr/formatos/$name/buscar.html",
            'encabezado'                => 1,
            'cuerpo'                    => '<p>{*showCalification*}</p><p>{*mostrarFirmas*}</p>',
            'pie_pagina'                => 0,
            'margenes'                  => '25,25,50,25',
            'orientacion'               => 0,
            'papel'                     => 'Letter',
            'funcionario_idfuncionario' => $idfuncionario,
            'detalle'                   => 1,
            'tipo_edicion'              => 0,
            'item'                      => 0,
            'font_size'                 => 11,
            'banderas'                  => 'e',
            'mostrar_pdf'               => 1,
            'orden'                     => null,
            'fk_categoria_formato'      => null,
            'pertenece_nucleo'          => 0,
            'descripcion_formato'       => 'Formulario de calificación de las PQR',
            'version'                   => 1,
            'publicar'                  => 1,
            'module'                    => 'pqr',
            'generador_pdf'             => 'Mpdf',
            'webservice'                => 1,
            'clase_ws'                  => 'App\Bundles\pqr\Services\generadoresWs\GenerateWsPqrCalificacion'
        ];

        $this->connection->insert('formato', $data);

        return (int)$this->connection->lastInsertId('formato');
    }

    private function createFields($idformato): void
    {
        $data = [
            'ft_pqr_respuesta'     => [
                'formato_idformato' => $idformato,
                'fila_visible'      => 1,
                'obligatoriedad'    => 1,
                'orden'             => 1,
                'nombre'            => 'ft_pqr_respuesta',
                'etiqueta'          => 'pqr_respuesta',
                'tipo_dato'         => 'integer',
                'banderas'          => 'i',
                'longitud'          => 11,
                'etiqueta_html'     => 'Method',
                'acciones'          => 'a',
                'listable'          => 1,
                'ayuda'             => null,
                'longitud_vis'      => null
            ],
            'experiencia_gestion'  => [
                'formato_idformato' => $idformato,
                'fila_visible'      => 1,
                'obligatoriedad'    => 1,
                'orden'             => 2,
                'nombre'            => 'experiencia_gestion',
                'etiqueta'          => 'Valora tu experiencia con la gestión a tu Petición, Queja, Reclamo o Solicitud',
                'tipo_dato'         => 'integer',
                'longitud'          => null,
                'etiqueta_html'     => 'Radio',
                'acciones'          => 'a,e,p',
                'placeholder'       => null,
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null,
                'campoOpciones'     => [
                    [
                        'llave' => 4,
                        'valor' => 'Excelente'
                    ],
                    [
                        'llave' => 3,
                        'valor' => 'Bueno'
                    ],
                    [
                        'llave' => 2,
                        'valor' => 'Regular'
                    ],
                    [
                        'llave' => 1,
                        'valor' => 'Deficiente'
                    ]
                ]
            ],
            'experiencia_servicio' => [
                'formato_idformato' => $idformato,
                'fila_visible'      => 1,
                'obligatoriedad'    => 1,
                'orden'             => 3,
                'nombre'            => 'experiencia_servicio',
                'etiqueta'          => 'Valora tu experiencia global con respecto a los servicios que has recibido',
                'tipo_dato'         => 'integer',
                'longitud'          => null,
                'etiqueta_html'     => 'Radio',
                'acciones'          => 'a,e',
                'placeholder'       => null,
                'listable'          => 1,
                'opciones'          => null,
                'ayuda'             => null,
                'longitud_vis'      => null,
                'campoOpciones'     => [
                    [
                        'llave' => 4,
                        'valor' => 'Excelente'
                    ],
                    [
                        'llave' => 3,
                        'valor' => 'Bueno'
                    ],
                    [
                        'llave' => 2,
                        'valor' => 'Regular'
                    ],
                    [
                        'llave' => 1,
                        'valor' => 'Deficiente'
                    ]
                ]
            ]
        ];

        foreach ($data as $field) {
            $campoOpciones = [];
            if (isset($field['campoOpciones'])) {
                $campoOpciones = $field['campoOpciones'];
                unset($field['campoOpciones']);
            }

            $this->connection->insert('campos_formato', $field);
            $id = $this->connection->lastInsertId('campos_formato');

            if ($campoOpciones) {
                foreach ($campoOpciones as $option) {
                    $dataOption = array_merge($option, [
                        'fk_campos_formato' => $id
                    ]);
                    $this->connection->insert('campo_opciones', $dataOption);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->deleteFormat($this->formatName, $schema);
    }
}
