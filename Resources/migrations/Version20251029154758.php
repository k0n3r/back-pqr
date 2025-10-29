<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Service\Mailer\Enum\TransportType;
use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251029154758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se adiciona las configuracion de email para PQR';
    }

    public function up(Schema $schema): void
    {
        $date = (new DateTime())->format($this->connection->getDatabasePlatform()->getDateTimeFormatString());
        $configurations = [
            [
                'event_key'      => 'pqr.radicado.solicitante',
                'description'    => '[PQR] Email enviado al solicitante al momento de radicar la PQR',
                'transport_type' => TransportType::DEFAULT->value,
                'active'         => 1,
                'created_at'     => $date,
                'updated_at'     => $date,
            ],
            [
                'event_key'      => 'pqr.radicado.configurados',
                'description'    => '[PQR] Email enviado a las funcionarios configurados cada que se crea una PQR',
                'transport_type' => TransportType::DEFAULT->value,
                'active'         => 1,
                'created_at'     => $date,
                'updated_at'     => $date,
            ],
            [
                'event_key'      => 'pqr.respuesta.respuesta',
                'description'    => '[Respuesta PQR] Email enviado al solicitante como respuesta a la PQR',
                'transport_type' => TransportType::DEFAULT->value,
                'active'         => 1,
                'created_at'     => $date,
                'updated_at'     => $date,
            ],
            [
                'event_key'      => 'pqr.respuesta.encuesta',
                'description'    => 'Email enviado para solicitar calificacion de la respuesta a la PQR',
                'transport_type' => TransportType::DEFAULT->value,
                'active'         => 1,
                'created_at'     => $date,
                'updated_at'     => $date,
            ],

        ];


        foreach ($configurations as $config) {
            $this->connection->insert('email_configuration', $config);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
