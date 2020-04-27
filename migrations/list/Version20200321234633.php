<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200321234633 extends AbstractMigration
{
    use TMigrations;

    protected $formatName = 'pqr_calificacion';

    public function getDescription(): string
    {
        return 'Creacion del formato calificacion de la PQR';
    }

    public function up(Schema $schema): void
    {
        $idformato = $this->createFormat();
        $this->createFields($idformato);
    }

    protected function createFormat()
    {
        $sql = "SELECT idcontador FROM contador WHERE nombre like 'apoyo'";
        $contador = $this->connection->executeQuery($sql)->fetchAll();

        if (!$contador[0]['idcontador']) {
            $this->abortIf(true, "El contador apoyo NO existe");
        }

        $sql = "SELECT idfuncionario FROM funcionario WHERE login='cerok'";
        $funcionario = $this->connection->executeQuery($sql)->fetchAll();

        if (!$funcionario[0]['idfuncionario']) {
            $this->abortIf(true, "El funcionario ceork NO existe");
        }

        $sqlCodPadre = "SELECT idformato FROM formato WHERE nombre='pqr_respuesta'";
        $codPadre = $this->connection->executeQuery($sqlCodPadre)->fetchAll();
        if (!$codPadre[0]['idformato']) {
            $this->abortIf(true, "No se encontro el formato padre Respuesta PQRSF");
        }

        $name = $this->formatName;
        $data = [
            'nombre' => $name,
            'etiqueta' => 'CALIFICACIÓN PQRSF',
            'cod_padre' => $codPadre[0]['idformato'],
            'contador_idcontador' => $contador[0]['idcontador'],
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "app/modules/back_pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '{*showCalification*}',
            'pie_pagina' => 0,
            'margenes' => '25,25,50,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'exportar' => 'mpdf',
            'funcionario_idfuncionario' => $funcionario[0]['idfuncionario'],
            'detalle' => 1,
            'tipo_edicion' => 0,
            'item' => 0,
            'font_size' => 11,
            'mostrar_pdf' => 1,
            'orden' => NULL,
            'firma_digital' => 0,
            'fk_categoria_formato' => NULL,
            'funcion_predeterminada' => 0,
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Formulario de calificación de las PQR',
            'version' => 1,
            'module' => 'pqr',
            'banderas' => 'e'

        ];

        $this->connection->insert('formato', $data);

        return $this->connection->lastInsertId();
    }

    protected function createFields($idformato): void
    {
        $data = [
            'ft_pqr_respuesta' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 1,
                'nombre' => 'ft_pqr_respuesta',
                'etiqueta' => 'pqr_respuesta',
                'tipo_dato' => 'integer',
                'banderas' => 'i',
                'longitud' => 11,
                'etiqueta_html' => 'Method',
                'acciones' => 'a',
                'listable' => 1,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'experiencia_gestion' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 2,
                'nombre' => 'experiencia_gestion',
                'etiqueta' => 'Valora tu experiencia con la gestión a tu Petición, Queja, Reclamo o Solicitud',
                'tipo_dato' => 'integer',
                'longitud' => NULL,
                'etiqueta_html' => 'Radio',
                'acciones' => 'a,e,p',
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL,
                'campoOpciones' => [
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
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 3,
                'nombre' => 'experiencia_servicio',
                'etiqueta' => 'Valora tu experiencia global con respecto a los servicios que has recibido',
                'tipo_dato' => 'integer',
                'longitud' => NULL,
                'etiqueta_html' => 'Radio',
                'acciones' => 'a,e',
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL,
                'campoOpciones' => [
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
            $campoOpciones = $field['campoOpciones'];
            unset($field['campoOpciones']);

            $this->connection->insert('campos_formato', $field);
            $id = $this->connection->lastInsertId();

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
