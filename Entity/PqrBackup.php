<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrBackupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrBackupRepository::class)]
#[ORM\Table(name: 'pqr_backups')]
#[ORM\Index(columns: ['fk_documento'], name: 'ipqr_backufk_docume')]
#[ORM\Index(columns: ['fk_pqr'], name: 'ipqr_backufk_pqr')]
class PqrBackup
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'fk_documento', type: 'integer', nullable: false)]
    private int $fkDocumento;

    #[ORM\Column(name: 'fk_pqr', type: 'integer', nullable: false)]
    private int $fkPqr;

    #[ORM\Column(name: 'data_json', type: 'json', nullable: false)]
    private array $dataJson = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getFkDocumento(): int
    {
        return $this->fkDocumento;
    }

    public function setFkDocumento(int $fkDocumento): self
    {
        $this->fkDocumento = $fkDocumento;
        return $this;
    }

    public function getFkPqr(): int
    {
        return $this->fkPqr;
    }

    public function setFkPqr(int $fkPqr): self
    {
        $this->fkPqr = $fkPqr;
        return $this;
    }

    public function getDataJson(): array
    {
        return $this->dataJson;
    }

    public function setDataJson(array $dataJson): self
    {
        $this->dataJson = $dataJson;
        return $this;
    }
}
