<?php

namespace App\Bundles\pqr\Command;

use App\Command\GenerateIndexesCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:generate:indexesPqr',
    description: 'Genera los indices del Modulo de PQR',
)]
class GenerateIndixesPqrCommand extends GenerateIndexesCommand
{
    protected function getIndixes(): array
    {
        return [
            'ft_pqr'              => [
                'documento_iddocumento',
                'sys_estado',
                'sys_tipo',
                'sys_oportuno',
                'sys_dependencia',
                [
                    'documento_iddocumento',
                    'sys_estado',
                ],
                [
                    'documento_iddocumento',
                    'sys_oportuno',
                ],
                [
                    'documento_iddocumento',
                    'sys_tipo',
                ],
                [
                    'documento_iddocumento',
                    'sys_dependencia',
                ],
            ],
            'ft_pqr_calificacion' => [
                'documento_iddocumento',
                'ft_pqr_respuesta',
            ],
            'ft_pqr_respuesta'    => [
                'documento_iddocumento',
                'ft_pqr',
            ],
            'pqr_backups'         => [
                'fk_documento',
                'fk_pqr',
            ],
            'pqr_balancer'        => [
                'fk_campo_opciones',
                'fk_sys_tipo',
            ],
            'pqr_forms'           => [
                'fk_formato',
                'fk_contador',
            ],
            'pqr_form_fields'     => [
                'fk_pqr_html_field',
                'fk_pqr_form',
                'fk_campos_formato',
            ],
            'pqr_history'         => [
                'idft',
                'fk_funcionario',
                'idfk',
            ],
            'pqr_html_fields'     => [
                'active',
            ],
            'pqr_notifications'   => [
                'fk_funcionario',
                'fk_pqr_form',
            ],
            'pqr_noty_messages'   => [
                'type',
            ],
            'pqr_response_times'  => [
                'fk_campo_opciones',
                'fk_sys_tipo',
            ],
        ];
    }
}
