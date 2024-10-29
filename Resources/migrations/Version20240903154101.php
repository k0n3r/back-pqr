<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240903154101 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se crea campo de configuracion de los canales de recepcion';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if (!$table->hasColumn('canal_recepcion')) {
            $driver = $this->connection->getParams()['driver'];
            $options = [];
            if ($driver == 'pdo_sqlsrv') {
                $chanels = json_encode([
                    'cFISICO',
                    'cTELEFONICO',
                    'cREDES'
                ]);

                $options = [
                    'default' => $chanels
                ];
            }
            $table->addColumn('canal_recepcion', 'json', $options);
        }

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        $table->dropColumn('canal_recepcion');
    }
}
