<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

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
        $idsHtmlFields = [];
        $fields = $this->getDataPqrHtmlFields();
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_html_fields',
                $field
            );
            $id = $this->connection->lastInsertId();

            $idsHtmlFields[$field['type']] = $id;
        }

        $fields = $this->getDataPqrResponseTemplates();
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_response_templates',
                $field
            );
        }

        $this->connection->insert(
            'pqr_forms',
            $this->getDataPqrForms()
        );
        $idform = (int) $this->connection->lastInsertId();


        $fields = $this->getDataPqrFormFields($idform, $idsHtmlFields);
        foreach ($fields as $field) {
            $this->connection->insert(
                'pqr_form_fields',
                $field
            );
        }
    }

    protected function getDataPqrHtmlFields(): array
    {
        return [
            [
                'label' => 'Linea de texto',
                'type' => 'input',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'E-mail',
                'type' => 'email',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Numérico',
                'type' => 'number',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Área de texto',
                'type' => 'textarea',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Listado',
                'type' => 'select',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Listado de Dependencia',
                'type' => 'dependencia',
                'active' => 1,
                'uniq' => 1
            ],
            [
                'label' => 'Selección Única',
                'type' => 'radio',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Selección múltiple',
                'type' => 'checkbox',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Autompletar de municipios',
                'type' => 'localidad',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Tratamiento de datos',
                'type' => 'tratamiento',
                'active' => 1,
                'uniq' => 1
            ]
        ];
    }

    protected function getDataPqrResponseTemplates(): array
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

    protected function getDataPqrForms(): array
    {
        $sql = "SELECT idcontador FROM contador WHERE nombre='radicacion_entrada'";
        $contador = $this->connection->fetchAll($sql);

        if (!$contador[0]['idcontador']) {
            $this->abortIf(true, 'El contador Externo-Interno NO existe');
        }

        return [
            'fk_formato' => 0,
            'fk_contador' => $contador[0]['idcontador'],
            'label' => 'PQRSF',
            'name' => 'pqr',
            'active' => 1
        ];
    }

    protected function getDataPqrFormFields(int $idform, array $idsHtmlFields): array
    {

        return [
            [
                'label' => 'Tipo',
                'name' => 'sys_tipo',
                'required' => 1,
                'system' => 1,
                'orden' => 2,
                'fk_pqr_html_field' => $idsHtmlFields['select'],
                'fk_pqr_form' => $idform,
                'setting' => json_encode([
                    'options' => [
                        'Petición',
                        'Queja',
                        'Reclamo',
                        'Sugerencia',
                        'Felicitación'
                    ]
                ])
            ],
            [
                'label' => 'E-mail',
                'name' => 'sys_email',
                'required' => 1,
                'system' => 1,
                'orden' => 3,
                'fk_pqr_html_field' => $idsHtmlFields['email'],
                'fk_pqr_form' => $idform,
                'setting' => json_encode([
                    'placeholder' => 'example@pqr.com'
                ])
            ]
        ];
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'pqr_html_fields',
            'pqr_response_templates',
            'pqr_forms',
            'pqr_form_fields'
        ];
        foreach ($tables as $table) {
            $this->addSql("TRUNCATE TABLE {$table}");
        }
    }
}
