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
        return 'Se agregan los registros de los tablas';
    }

    public function up(Schema $schema): void
    {
        $fields = $this->getHtmlFields();
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_html_fields',
                $field
            );
        }


        $fields = $this->getTemplateFields();
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_response_templates',
                $field
            );
        }
    }

    protected function getHtmlFields(): array
    {
        return [
            [
                'label' => 'Cuadro de texto',
                'type' => 'input',
                'active' => 1
            ],
            [
                'label' => 'Área de texto',
                'type' => 'textarea',
                'active' => 1
            ],
            [
                'label' => 'E-mail',
                'type' => 'email',
                'active' => 1
            ],
            [
                'label' => 'Lista desplegable',
                'type' => 'select',
                'active' => 1
            ],
            [
                'label' => 'Selección Única',
                'type' => 'radio',
                'active' => 1
            ],
            [
                'label' => 'Selección múltiple',
                'type' => 'checkbox',
                'active' => 1
            ]
        ];
    }

    protected function getTemplateFields(): array
    {
        return [
            [
                'name' => "Comunicación Externa No 1",
                'content' => '<table border="0"><tbody><tr><td>Pereira, 1 de Abril 2020</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Señor<br>JORGE RAMIREZ<br>CEO<br>Cra 12b No 8-47<br>3165210445</td></tr><tr><td>&nbsp;</td></tr><tr><td>ASUNTO: Creación de plantilla</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Cordial saludo:</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td><h4>The standard Lorem Ipsum passage, used since the 1500s</h4><p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p><h4>Section 1.10.32 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC</h4><p>"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?"</p></td></tr><tr><td>&nbsp;</td></tr><tr><td>Atentamente,</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Proyectó: Cero k</td></tr></tbody></table>',
                'system' => 1
            ],
            [
                'name' => "Comunicación Externa No 2",
                'content' => '<table border="0"><tbody><tr><td>Pereira, 1 de Abril 2020</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Señor<br>JORGE RAMIREZ<br>CEO<br>Cra 12b No 8-47<br>3165210445</td></tr><tr><td>&nbsp;</td></tr><tr><td>ASUNTO: Creación de plantilla</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Cordial saludo:</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td><h4>The standard Lorem Ipsum passage, used since the 1500s</h4><p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p><h4>Section 1.10.32 of "de Finibus Bonorum et Malorum", written by Cicero in 45 BC</h4><p>"Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?"</p></td></tr><tr><td>&nbsp;</td></tr><tr><td>Atentamente,</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>&nbsp;</td></tr><tr><td>Proyectó: Cero k</td></tr></tbody></table>',
                'system' => 1
            ]
        ];
    }

    public function down(Schema $schema): void
    {
        $this->addSql("TRUNCATE TABLE pqr_html_fields");
        $this->addSql("TRUNCATE TABLE pqr_response_templates");
    }
}
