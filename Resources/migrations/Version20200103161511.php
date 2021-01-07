<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

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

        $fields = $this->getDataPqrNotyMessages();
        foreach ($fields as $field) {
            $this->connection->insert('pqr_noty_messages', $field);
        }
    }

    protected function getDataPqrHtmlFields(): array
    {
        return [
            [
                'label' => 'Linea de texto',
                'type' => 'text',
                'type_saia' => 'Text',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Numérico',
                'type' => 'number',
                'type_saia' => 'Text',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'E-mail',
                'type' => 'email',
                'type_saia' => 'Text',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Área de texto',
                'type' => 'textarea',
                'type_saia' => 'Textarea',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Listado',
                'type' => 'select',
                'type_saia' => 'Select',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Listado de Dependencia',
                'type' => 'dependencia',
                'type_saia' => 'AutocompleteD',
                'active' => 1,
                'uniq' => 1
            ],
            [
                'label' => 'Selección Única',
                'type' => 'radio',
                'type_saia' => 'Radio',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Selección múltiple',
                'type' => 'checkbox',
                'type_saia' => 'Checkbox',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Autompletar de municipios',
                'type' => 'localidad',
                'type_saia' => 'AutocompleteM',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Tratamiento de datos',
                'type' => 'tratamiento',
                'type_saia' => 'Hidden',
                'active' => 1,
                'uniq' => 1
            ],
            [
                'label' => 'Anexos',
                'type' => 'file',
                'type_saia' => 'Attached',
                'active' => 1,
                'uniq' => 0
            ],
            [
                'label' => 'Categoria de tipos',
                'type' => 'subTypesPqr',
                'type_saia' => 'Select',
                'active' => 1,
                'uniq' => 1
            ],
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
                'anonymous' => 1,
                'show_report' => 0,
                'setting' => json_encode([
                    'options' => [
                        [
                            'text' => 'Petición',
                            'dias' => 15
                        ],
                        [
                            'text' => 'Queja',
                            'dias' => 15
                        ],
                        [
                            'text' => 'Reclamo',
                            'dias' => 15
                        ],
                        [
                            'text' => 'Sugerencia',
                            'dias' => 15
                        ],
                        [
                            'text' => 'Felicitación',
                            'dias' => 15
                        ]
                    ]
                ]),
                'fk_pqr_html_field' => $idsHtmlFields['select'],
                'fk_pqr_form' => $idform,
                'is_system' => 1,
                'orden' => 2,
                'required_anonymous' => 1
            ],
            [
                'label' => 'E-mail',
                'name' => 'sys_email',
                'required' => 1,
                'anonymous' => 1,
                'show_report' => 1,
                'setting' => json_encode([
                    'placeholder' => 'example@pqr.com'
                ]),
                'fk_pqr_html_field' => $idsHtmlFields['email'],
                'fk_pqr_form' => $idform,
                'is_system' => 1,
                'orden' => 3
            ]
        ];
    }

    protected function getDataPqrNotyMessages()
    {
        return [
            [
                'name' => 'ws_noty_radicado',
                'label' => 'Radicar solicitud (Notificación)',
                'description' => 'Mensaje informativo que se muestra al radicar la solicitud desde el webservice',
                'subject' => NULL,
                'message_body' => '<br/>Su solicitud ha sido generada con el número de radicado <strong>{*n_numeroPqr*}</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!',
                'type' => 1,
                'active' => 1,
            ],
            [
                'name' => 'f1_email_solicitante',
                'label' => 'Radicar solicitud (E-mail)',
                'description' => 'E-mail que se envia al radicar una solicitud',
                'subject' => 'Solicitud de {*n_nombreFormularioPqr*} # {*n_numeroPqr*}',
                'message_body' => 'Cordial Saludo,<br/><br/>Su solicitud ha sido generada con el número de radicado {*n_numeroPqr*}, adjunto encontrará una copia de la información diligenciada el día de hoy.<br/><br/>El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado',
                'type' => 2,
                'active' => 1,
            ],
            [
                'name' => 'f2_email_respuesta',
                'label' => 'Respuesta a la solicitud (E-mail)',
                'description' => 'E-mail que se envia al generar una respuesta a la solicitud',
                'subject' => 'Respuesta solicitud de {*n_nombreFormularioPqr*} # {*n_numeroPqr*}',
                'message_body' => 'Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {*n_nombreFormularioPqr*} con número de radicado {*n_numeroPqr*}.<br/><br/>',
                'type' => 2,
                'active' => 1,
            ]
        ];
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'pqr_html_fields',
            'pqr_forms',
            'pqr_form_fields',
            'pqr_noty_messages'
        ];
        foreach ($tables as $table) {
            $this->addSql("TRUNCATE TABLE {$table}");
        }
    }
}
