<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Saia\controllers\generator\component\Method;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240625141913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea campo cerrar_tareas, en la respuesta pqr';
    }

    public function up(Schema $schema): void
    {
        //TODO Se puede eliminar

        $sql = "SELECT idformato FROM formato WHERE nombre LIKE 'pqr_respuesta'";
        $idformato = $this->connection->fetchOne($sql);

        $sql = "SELECT idcampos_formato FROM campos_formato WHERE nombre LIKE 'cerrar_tareas' AND formato_idformato=$idformato";
        $exist = $this->connection->fetchOne($sql);

        if ($exist) {
            return;
        }

        $this->connection->insert('campos_formato', [
            'formato_idformato' => $idformato,
            'nombre'            => 'cerrar_tareas',
            'etiqueta'          => 'Desea cerrar las tareas de la PQRSF?',
            'valor'             => '{*showCloseTask@SI,NO*}',
            'tipo_dato'         => 'integer',
            'longitud'          => '1',
            'obligatoriedad'    => 1,
            'acciones'          => 'a,e',
            'etiqueta_html'     => Method::getIdentification(),
            'orden'             => 15,
            'fila_visible'      => 1,
            'listable'          => 1,
            'predeterminado'    => 0
        ]);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
