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
            'etiqueta' => 'COMUNICACIÓN EXTERNA (PQRSF)',
            'cod_padre' => 0,
            'contador_idcontador' => $contador[0]['idcontador'],
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "app/modules/back_pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '<p>{*showTemplate*}</p>',
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
                'nombre' => 'ft_pqr',
                'etiqueta' => 'pqr',
                'tipo_dato' => 'integer',
                'longitud' => '11',
                'obligatoriedad' => '1',
                'acciones' => 'a',
                'banderas' => 'i',
                'etiqueta_html' => 'Method',
                'orden' => '1',
                'fila_visible' => '1',
                'listable' => '1'
            ],
            'ciudad_origen' => [
                'formato_idformato' => $idformato,
                'nombre' => 'ciudad_origen',
                'etiqueta' => 'Ciudad de Origen',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '1',
                'valor' => '{*selectCity*}',
                'acciones' => 'a,e',
                'etiqueta_html' => 'Method',
                'orden' => '2',
                'fila_visible' => '1',
                'listable' => '1'
            ],
            'destino' => [
                'formato_idformato' => $idformato,
                'nombre' => 'destino',
                'etiqueta' => 'Destino',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '1',
                'acciones' => 'a,e',
                'etiqueta_html' => 'ExternalUser',
                'orden' => '3',
                'fila_visible' => '1',
                'opciones' => '{"tipo_seleccion":"unico","tipo":true,"nombre":true,"correo":true,"tipo_identificacion":true,"identificacion":true,"ciudad":true,"titulo":true,"cargo":false,"direccion":true,"telefono":true,"sede":false,"empresa":false}',
                'listable' => '1'
            ],
            'tipo_distribucion' => [
                'formato_idformato' => $idformato,
                'nombre' => 'tipo_distribucion',
                'etiqueta' => 'Tipo distribución',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '1',
                'valor' => '1,1;2,2;3,3;4,4',
                'acciones' => 'a,e',
                'etiqueta_html' => 'Select',
                'orden' => '4',
                'fila_visible' => '1',
                'placeholder' => 'seleccionar..',
                'listable' => '1',
                'campoOpciones' => [
                    ['llave' => '4', 'valor' => 'Enviar por E-mail', 'estado' => '1', 'orden' => '0'],
                    ['llave' => '1', 'valor' => 'Requiero recogida de documento y entrega al destinatario', 'estado' => '1', 'orden' => '1'],
                    ['llave' => '2', 'valor' => 'Requiero sólo entrega al destinatario', 'estado' => '1', 'orden' => '2'],
                    ['llave' => '3', 'valor' => 'No requiero servicio de mensajería, lo entregaré yo mismo', 'estado' => '1', 'orden' => '3'],
                ]
            ],
            'copia' => [
                'formato_idformato' => $idformato,
                'nombre' => 'copia',
                'etiqueta' => 'Con copia a',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '0',
                'acciones' => 'a,e',
                'etiqueta_html' => 'ExternalUser',
                'orden' => '5',
                'fila_visible' => '1',
                'opciones' => '{"tipo_seleccion":"unico","tipo":true,"nombre":true,"correo":true,"tipo_identificacion":true,"identificacion":true,"ciudad":true,"titulo":true,"cargo":false,"direccion":true,"telefono":true,"sede":false,"empresa":false}',
                'listable' => '1'
            ],
            'asunto' => [
                'formato_idformato' => $idformato,
                'nombre' => 'asunto',
                'etiqueta' => 'Asunto',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '1',
                'acciones' => 'a,e,p,b',
                'etiqueta_html' => 'Text',
                'orden' => '6',
                'fila_visible' => '1',
                'longitud_vis' => '255',
                'listable' => '1'
            ],
            'contenido' => [
                'formato_idformato' => $idformato,
                'nombre' => 'contenido',
                'etiqueta' => 'Contenido',
                'tipo_dato' => 'text',
                'obligatoriedad' => '1',
                'valor' => 'avanzado',
                'acciones' => 'a,e,p',
                'etiqueta_html' => 'Textarea',
                'orden' => '7',
                'fila_visible' => '1',
                'opciones' => '{"avanzado":true}',
                'listable' => '1'
            ],
            'despedida' => [
                'formato_idformato' => $idformato,
                'nombre' => 'despedida',
                'etiqueta' => 'Despedida',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '1',
                'valor' => '1,1;2,2;3,3;4,4',
                'acciones' => 'a,e',
                'etiqueta_html' => 'Select',
                'orden' => '8',
                'fila_visible' => '1',
                'placeholder' => 'seleccionar..',
                'listable' => '1'
            ],
            'otra_despedida' => [
                'formato_idformato' => $idformato,
                'nombre' => 'otra_despedida',
                'etiqueta' => 'Escribe la despedida',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '0',
                'acciones' => 'a,e',
                'etiqueta_html' => 'Text',
                'orden' => '9',
                'fila_visible' => '1',
                'longitud_vis' => '255',
                'listable' => '1'
            ],
            'anexos_digitales' => [
                'formato_idformato' => $idformato,
                'nombre' => 'anexos_digitales',
                'etiqueta' => 'Anexos Digitales',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '0',
                'valor' => '.pdf|.doc|.docx|.jpg|.jpeg|.gif|.png|.bmp|.xls|.xlsx|.ppt@multiple',
                'acciones' => 'a,e,b',
                'banderas' => 'a',
                'etiqueta_html' => 'Attached',
                'orden' => '10',
                'fila_visible' => '1',
                'opciones' => '{"tipos":".pdf,.doc,.docx,.jpg,.jpeg,.gif,.png,.bmp,.xls,.xlsx,.ppt","longitud":"3","cantidad":"10","ruta_consulta":"app\\/anexos\\/consultar_anexos_campo.php"}',
                'listable' => '1'
            ],
            'anexos_fisicos' => [
                'formato_idformato' => $idformato,
                'nombre' => 'anexos_fisicos',
                'etiqueta' => 'Anexos Fisicos',
                'tipo_dato' => 'text',
                'obligatoriedad' => '0',
                'acciones' => 'a,e',
                'ayuda' => 'Por favor separar los anexos con comas ","',
                'etiqueta_html' => 'Text',
                'orden' => '11',
                'fila_visible' => '1',
                'listable' => '1'
            ],
            'copia_interna' => [
                'formato_idformato' => $idformato,
                'nombre' => 'copia_interna',
                'etiqueta' => 'Con copia interna a',
                'tipo_dato' => 'string',
                'longitud' => '255',
                'obligatoriedad' => '0',
                'acciones' => 'a,e',
                'etiqueta_html' => 'UserAutocomplete',
                'orden' => '12',
                'fila_visible' => '1',
                'opciones' => '{"dependenciaCargo":true}',
                'listable' => '1'
            ],
            'sol_encuesta' => [
                'formato_idformato' => $idformato,
                'nombre' => 'sol_encuesta',
                'etiqueta' => 'Solicitar la encuesta del servicio',
                'tipo_dato' => 'integer',
                'longitud' => '1',
                'obligatoriedad' => 0,
                'valor' => '{*fieldSatisfactionSurvey*}',
                'acciones' => 'a',
                'etiqueta_html' => 'Method',
                'orden' => '13',
                'fila_visible' => '1',
                'listable' => '1'
            ]
        ];

        foreach ($data as $nameField => $field) {
            $campoOpciones = [];
            if ($nameField == "tipo_distribucion") {
                $campoOpciones = $field['campoOpciones'];
                unset($field['campoOpciones']);
            }

            $this->connection->insert('campos_formato', $field);
            $id = $this->connection->lastInsertId();
            if ($campoOpciones) {
                foreach ($campoOpciones as $row) {
                    $row['fk_campos_formato'] = $id;
                    $this->connection->insert('campo_opciones', $row);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->deleteFormat($this->formatName, $schema);
    }
}
