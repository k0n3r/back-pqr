<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220624002509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se agrega el campo ver_copia a la respuesta pqr';
    }

    public function up(Schema $schema): void
    {
        $idformato = $this->getIdFormatoPqr();
        $sql = "SELECT idcampos_formato FROM campos_formato WHERE nombre LIKE 'ver_copia' AND formato_idformato=$idformato";
        $exist = $this->connection->fetchOne($sql);

        if (!$exist) {
            $data = [
                'formato_idformato' => $idformato,
                'nombre'            => 'ver_copia',
                'etiqueta'          => 'Mostrar Copia Interna',
                'valor'             => '{*showCopia@SI,NO*}',
                'tipo_dato'         => 'integer',
                'longitud'          => '1',
                'obligatoriedad'    => 1,
                'acciones'          => 'a,e',
                'etiqueta_html'     => 'Method',
                'orden'             => '15',
                'fila_visible'      => '1',
                'listable'          => '1',
                'predeterminado'    => 0
            ];
            $this->connection->insert('campos_formato', $data);
        }

    }

    private function getIdFormatoPqr(): int
    {
        $sql = "SELECT idformato FROM formato WHERE nombre LIKE 'pqr_respuesta'";
        $idformato = $this->connection->fetchOne($sql);
        return (int)$idformato;
    }

    public function down(Schema $schema): void
    {

    }
}
