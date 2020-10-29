<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations\lista;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200930004556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se agregan los registros de las notificaciones';
    }

    public function up(Schema $schema): void
    {
        $records = [
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

        foreach ($records as $row) {
            $this->connection->insert('pqr_noty_messages', $row);
        }
    }

    public function down(Schema $schema): void
    {
        $sql = "TRUNCATE TABLE pqr_noty_messages";
        $this->addSql($sql);
    }
}
