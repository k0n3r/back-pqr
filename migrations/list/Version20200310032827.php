<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200310032827 extends AbstractMigration
{
    protected $formatName = 'pqr_respuesta';

    public function getDescription(): string
    {
        return 'Creacion del formato respuesta PQR';
    }

    public function up(Schema $schema): void
    {
        $idformato = $this->createFormat();
        $this->createFields($idformato);
    }

    protected function createFormat()
    {
        $sql = "SELECT idcontador FROM contador WHERE nombre like 'radicacion_salida'";
        $contador = $this->connection->executeQuery($sql)->fetchAll();

        if (!$contador[0]['idcontador']) {
            $this->abortIf(true, "El contador radicacion_salida NO existe");
        }

        $sql = "SELECT idfuncionario FROM funcionario WHERE login='cerok'";
        $funcionario = $this->connection->executeQuery($sql)->fetchAll();

        if (!$funcionario[0]['idfuncionario']) {
            $this->abortIf(true, "El contador radicacion_salida NO existe");
        }

        $name = $this->formatName;
        $data = [
            'nombre' => $name,
            'etiqueta' => 'RESPUESTA PQR',
            'cod_padre' => 0,
            'contador_idcontador' => $contador[0]['idcontador'],
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "app/modules/back_pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '{*showTemplate*}{*mostrar_estado_proceso*}',
            'pie_pagina' => 0,
            'margenes' => '25,25,25,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'exportar' => 'mpdf',
            'funcionario_idfuncionario' => $funcionario[0]['idfuncionario'],
            'detalle' => 0,
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
            'descripcion_formato' => 'Formulario utilizado para responder las PQR',
            'version' => 1,
            'module' => 'pqr'
        ];

        $this->connection->insert('formato', $data);

        return $this->connection->lastInsertId();
    }

    protected function createFields($idformato): void
    {
        $data = [
            'fk_response_template' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 1,
                'nombre' => 'fk_response_template',
                'etiqueta' => 'Plantilla',
                'tipo_dato' => 'integer',
                'longitud' => 11,
                'etiqueta_html' => 'opciones_sql',
                'acciones' => 'a',
                'placeholder' => '',
                'listable' => 1,
                'opciones' => '{"tipo":"select","sql":"SELECT id,name as nombre FROM pqr_response_templates"}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'fk_response_template_json' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 1,
                'nombre' => 'fk_response_template_json',
                'etiqueta' => 'fk_response_template_json',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'system_field',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 0,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'email' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 2,
                'nombre' => 'email',
                'etiqueta' => 'Responder a (Email):',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'text',
                'acciones' => 'a,e,p',
                'placeholder' => 'Ingrese los correos',
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => 'Ingrese el o los correos, separados por coma a los cuales se le remitira la respuesta',
                'longitud_vis' => NULL
            ],
            'content' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 3,
                'nombre' => 'content',
                'etiqueta' => 'Contenido',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'textarea_cke',
                'acciones' => 'a,e',
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"avanzado":true}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'adjuntos' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 4,
                'valor' => '.pdf|.doc|.docx|.jpg|.jpeg|.gif|.png|.bmp|.xls|.xlsx|.ppt@multiple',
                'nombre' => 'adjuntos',
                'etiqueta' => 'Anexos',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'archivo',
                'acciones' => 'a,e',
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"tipos":".pdf,.doc,.docx,.jpg,.jpeg,.gif,.png,.bmp,.xls,.xlsx,.ppt","longitud":"3","cantidad":"3"}',
                'ayuda' => 'Anexos que se enviaran en la respuesta',
                'longitud_vis' => NULL
            ]
        ];

        foreach ($data as $field) {
            $this->connection->insert('campos_formato', $field);
        }
    }

    public function down(Schema $schema): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre like '{$this->formatName}'";
        $data = $this->connection->executeQuery($sql)->fetchAll();
        if (!$data[0]['idformato']) {
            $this->abortIf(true, "No se encontro el formato {$this->formatName}");
        }

        $idformato = $data[0]['idformato'];
        $this->connection->delete(
            'campos_formato',
            [
                'formato_idformato' => $idformato
            ]
        );

        $this->connection->delete(
            'formato',
            [
                'idformato' => $idformato
            ]
        );
    }
}
