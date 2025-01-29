<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250129202500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se eliminan migraciones que no son necesarias y se valida que se hayan corrido';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT COUNT(*) as cant FROM migrations WHERE version LIKE '%pqr%'";
        $cantMigrations = $this->connection->fetchOne($sql);
        if ($cantMigrations == 7) {
            return;
        }


        $migrations = $this->migrations();

        foreach ($migrations as $migration) {
            $sql = "SELECT version FROM migrations WHERE version LIKE '%pqr%$migration'";
            $exist = $this->connection->fetchOne($sql);
            if ($exist) {
                $delete = "DELETE FROM migrations WHERE version LIKE '%pqr%$migration'";
                $this->connection->executeStatement($delete);
            } else {
                $this->abortIf(true,
                    "si es una instalacion nueva de PQR haga un return al inicio, de lo contrario debe correr las migraciones en la rama master");
            }
        }

    }

    private function migrations(): array
    {
        return [
            'Version20210411151159',
            'Version20210605164743',
            'Version20210714002400',
            'Version20210806161650',
            'Version20211005000341',
            'Version20211005000342',
            'Version20220214144616',
            'Version20220216212854',
            'Version20220406164056',
            'Version20220624002509',
            'Version20220701154843',
            'Version20221021021344',
            'Version20230602192203',
            'Version20230622231509',
            'Version20230623201856',
            'Version20230626155031',
            'Version20230925163206',
            'Version20230925163207',
            'Version20240216213721',
            'Version20240408223049',
            'Version20240625141913',
            'Version20240829222106',
            'Version20240903154101',
            'Version20240903154653',
            'Version20241125170344'
        ];
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
