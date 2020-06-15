<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200310032827 extends AbstractMigration
{
    use TMigrations;

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
            $this->abortIf(true, "El funcionario cerok NO existe");
        }

        $name = $this->formatName;
        $data = [
            'nombre' => $name,
            'etiqueta' => 'RESPUESTAS PQRSF',
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
            'margenes' => '25,25,50,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'funcionario_idfuncionario' => $funcionario[0]['idfuncionario'],
            'detalle' => 1,
            'tipo_edicion' => 0,
            'item' => 0,
            'font_size' => 11,
            'banderas' => 'e',
            'mostrar_pdf' => 1,
            'orden' => NULL,
            'fk_categoria_formato' => NULL,
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Formulario utilizado para responder las PQRSF',
            'version' => 1,
            'publicar' => 1,
            'module' => 'pqr',
            'class_name' => NULL,
            'rad_email' => 0,
            'generador_pdf' => 'Mpdf'
        ];

        $this->connection->insert('formato', $data);

        return $this->connection->lastInsertId();
    }

    protected function createFields($idformato): void
    {
        $data = [
            'ft_pqr' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 0,
                'nombre' => 'ft_pqr',
                'etiqueta' => 'pqr',
                'tipo_dato' => 'integer',
                'banderas' => 'i',
                'longitud' => 11,
                'etiqueta_html' => 'Method',
                'acciones' => 'a',
                'listable' => 1,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'email' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 1,
                'nombre' => 'email',
                'etiqueta' => 'Responder a (E-mail):',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'Text',
                'acciones' => 'a,e',
                'placeholder' => 'Ingrese el correo',
                'listable' => 1,
                'opciones' => '{"type":"email"}',
                'ayuda' => 'Ingrese el correo del remitente a la cual dara respuesta a la PQR',
                'longitud_vis' => NULL
            ],
            'email_copia' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 2,
                'nombre' => 'email_copia',
                'etiqueta' => 'Copia a (E-mail):',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'Text',
                'acciones' => 'a,e',
                'placeholder' => 'Ingrese los correos',
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => 'Ingrese los correos separados por coma, a los cuales se le copiara la respuesta',
                'longitud_vis' => NULL
            ],
            'fk_response_template' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 3,
                'nombre' => 'fk_response_template',
                'etiqueta' => 'Plantilla',
                'tipo_dato' => 'integer',
                'longitud' => 11,
                'etiqueta_html' => 'SqlOptions',
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
                'orden' => 4,
                'nombre' => 'fk_response_template_json',
                'etiqueta' => 'fk_response_template_json',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'SystemField',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 0,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'content' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 5,
                'nombre' => 'content',
                'etiqueta' => 'Contenido',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'Textarea',
                'acciones' => 'a,e,p',
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
                'orden' => 6,
                'valor' => '.pdf|.doc|.docx|.jpg|.jpeg|.gif|.png|.bmp|.xls|.xlsx|.ppt@multiple',
                'nombre' => 'adjuntos',
                'etiqueta' => 'Anexos',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'Attached',
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
        $this->deleteFormat($this->formatName, $schema);
    }
}
