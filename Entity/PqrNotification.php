<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrNotificationRepository::class)]
#[ORM\Table(name: 'pqr_notifications')]
#[ORM\Index(columns: ['fk_funcionario'], name: 'ipqr_notiffk_funcio')]
#[ORM\Index(columns: ['fk_pqr_form'], name: 'ipqr_notiffk_pqr_fo')]
class PqrNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'fk_funcionario', type: 'integer', nullable: false)]
    private int $fkFuncionario;

    #[ORM\Column(name: 'fk_pqr_form', type: 'integer', nullable: false)]
    private int $fkPqrForm;

    #[ORM\Column(name: 'email', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $email = false;

    #[ORM\Column(name: 'notify', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $notify = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFkFuncionario(): int
    {
        return $this->fkFuncionario;
    }

    public function setFkFuncionario(int $fkFuncionario): self
    {
        $this->fkFuncionario = $fkFuncionario;
        return $this;
    }

    public function getFkPqrForm(): int
    {
        return $this->fkPqrForm;
    }

    public function setFkPqrForm(int $fkPqrForm): self
    {
        $this->fkPqrForm = $fkPqrForm;
        return $this;
    }

    public function isEmail(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function isNotify(): bool
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;
        return $this;
    }
}
