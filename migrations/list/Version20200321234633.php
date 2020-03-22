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
            $this->abortIf(true, "No se encontro el formato padre Respuesta PQR");
        }

        $name = $this->formatName;
        $data = [
            'nombre' => $name,
            'etiqueta' => 'CALIFICACIÓN PQR',
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
            'descripcion_formato' => 'Formulario de calificación de las PQR',
            'version' => 1,
            'module' => 'pqr'
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
            'campo' => [
                'formato_idformato' => $idformato,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 2,
                'nombre' => 'campo',
                'etiqueta' => 'Campo:',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'Text',
                'acciones' => 'a,e,p',
                'placeholder' => 'Ingrese el texto',
                'listable' => 1,
                'opciones' => '{type:"text"}',
                'ayuda' => 'Ingrese texto',
                'longitud_vis' => NULL
            ]
        ];

        foreach ($data as $field) {
            $this->connection->insert('campos_formato', $field);
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

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

        $table = "ft_{$this->formatName}";
        if ($schema->hasTable($table)) {
            $schema->dropTable($table);
        }
    }
}
