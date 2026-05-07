<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrResponseTimeRepository::class)]
#[ORM\Table(name: 'pqr_response_times')]
class PqrResponseTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'fk_campo_opciones', type: 'integer', nullable: false)]
    private int $fkCampoOpciones;

    #[ORM\Column(name: 'fk_sys_tipo', type: 'integer', nullable: false, options: ['comment' => 'idcampo_opciones'])]
    private int $fkSysTipo;

    #[ORM\Column(name: 'number_days', type: 'integer', nullable: false)]
    private int $numberDays;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $active = true;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFkCampoOpciones(): int
    {
        return $this->fkCampoOpciones;
    }

    public function setFkCampoOpciones(int $fkCampoOpciones): self
    {
        $this->fkCampoOpciones = $fkCampoOpciones;
        return $this;
    }

    public function getFkSysTipo(): int
    {
        return $this->fkSysTipo;
    }

    public function setFkSysTipo(int $fkSysTipo): self
    {
        $this->fkSysTipo = $fkSysTipo;
        return $this;
    }

    public function getNumberDays(): int
    {
        return $this->numberDays;
    }

    public function setNumberDays(int $numberDays): self
    {
        $this->numberDays = $numberDays;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
}
