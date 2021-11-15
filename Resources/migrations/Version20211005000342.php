<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005000342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea el campo anexos del sistema';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT id FROM pqr_form_fields WHERE name LIKE 'sys_anexos'";
        $exist = (int)$this->connection->fetchOne($sql);

        if (!$exist) {
            $this->createField();
        }
    }

    public function down(Schema $schema): void
    {
        $sql = "SELECT id FROM pqr_form_fields WHERE name LIKE 'sys_anexos'";
        $exist = (int)$this->connection->fetchOne($sql);

        if ($exist) {
            $this->connection->delete('pqr_form_fields', [
                'name' => 'sys_anexos'
            ]);
        }

        $table = $schema->getTable('ft_pqr');
        if ($table->hasColumn('sys_anexos')) {
            $table->dropColumn('sys_anexos');
        }
    }

    private function createField(): void
    {
        $sql = "SELECT id FROM pqr_forms WHERE active=1";
        $idform = $this->connection->fetchOne($sql);

        $sql = "SELECT id FROM pqr_html_fields WHERE type LIKE 'file'";
        $idtipo = $this->connection->fetchOne($sql);

        $data = [
            'label'             => 'Anexos',
            'name'              => 'sys_anexos',
            'required'          => 0,
            'anonymous'         => 0,
            'show_report'       => 0,
            'setting'           => json_encode([
                'numberFiles' => 5,
                'typeFiles'   => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.bmp,.xls,.xlsx,.ppt,.zip,.xml'
            ]),
            'fk_pqr_html_field' => $idtipo,
            'fk_pqr_form'       => $idform,
            'is_system'         => 1,
            'orden'             => 4,
            'active'            => 1
        ];

        $this->connection->insert('pqr_form_fields', $data);
    }
}
