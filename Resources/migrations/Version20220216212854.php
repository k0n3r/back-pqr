<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220216212854 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Se crea el campo folios del sistema';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT id FROM pqr_form_fields WHERE name LIKE 'sys_folios'";
        $exist = (int)$this->connection->fetchOne($sql);

        if (!$exist) {
            $this->createField();
        }
    }

    public function down(Schema $schema): void
    {
        $sql = "SELECT id FROM pqr_form_fields WHERE name LIKE 'sys_folios'";
        $exist = (int)$this->connection->fetchOne($sql);

        if ($exist) {
            $this->connection->delete('pqr_form_fields', [
                'name' => 'sys_folios'
            ]);
        }

        $table = $schema->getTable('ft_pqr');
        if ($table->hasColumn('sys_folios')) {
            $table->dropColumn('sys_folios');
        }
    }

    private function createField(): void
    {
        $sql = "SELECT id FROM pqr_forms WHERE active=1";
        $idform = $this->connection->fetchOne($sql);

        $sql = "SELECT id FROM pqr_html_fields WHERE type LIKE 'number'";
        $idtipo = $this->connection->fetchOne($sql);

        $data = [
            'label'             => 'Número de folios',
            'name'              => 'sys_folios',
            'required'          => 0,
            'anonymous'         => 0,
            'show_report'       => 0,
            'setting'           => json_encode([
                'placeholder' => 'Ingrese el número de folios recibidos'
            ]),
            'fk_pqr_html_field' => $idtipo,
            'fk_pqr_form'       => $idform,
            'is_system'         => 1,
            'orden'             => 3,
            'active'            => 1
        ];

        $this->connection->insert('pqr_form_fields', $data);
    }
}
