<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200103161511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se agregan los registros de los campos HTML';
    }

    public function up(Schema $schema): void
    {
        $fields = $this->fields();
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_html_fields',
                $field
            );
        }
    }

    public function fields()
    {
        return [
            [
                'label' => 'Cuadro de texto',
                'type' => 'input',
                'type_saia' => 'string',
                'active' => 1
            ],
            [
                'label' => 'Área de texto',
                'type' => 'textarea',
                'type_saia' => 'text',
                'active' => 1
            ],
            [
                'label' => 'Lista desplegable',
                'type' => 'select',
                'type_saia' => 'string',
                'active' => 1
            ],
            [
                'label' => 'Selección Única',
                'type' => 'radio',
                'type_saia' => 'string',
                'active' => 1
            ],
            [
                'label' => 'Selección múltiple',
                'type' => 'checkbox',
                'type_saia' => 'string',
                'active' => 1
            ]
        ];
    }

    public function down(Schema $schema): void
    {
        $this->addSql("TRUNCATE TABLE pqr_html_fields");
    }
}
