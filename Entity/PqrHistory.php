<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrHistoryRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrHistoryRepository::class)]
#[ORM\Table(name: 'pqr_history')]
class PqrHistory
{
    public const int TIPO_TAREA = 1;
    public const int TIPO_NOTIFICACION = 2;
    public const int TIPO_RESPUESTA = 3;
    public const int TIPO_CAMBIO_ESTADO = 4;
    public const int TIPO_CAMBIO_VENCIMIENTO = 5;
    public const int TIPO_CALIFICACION = 6;
    public const int TIPO_ERROR_DIAS_VENCIMIENTO = 7;
    public const int TIPO_MODIFICACION_TERCERO = 8;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'idft', type: 'integer', nullable: false)]
    private int $idft;

    #[ORM\Column(name: 'fecha', type: 'datetime', nullable: false)]
    private DateTimeInterface $fecha;

    #[ORM\Column(name: 'fk_funcionario', type: 'integer', nullable: false)]
    private int $fkFuncionario;

    #[ORM\Column(name: 'tipo', type: 'integer', nullable: false)]
    private int $tipo;

    #[ORM\Column(name: 'idfk', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $idfk = 0;

    #[ORM\Column(name: 'descripcion', type: 'text', nullable: false)]
    private string $descripcion;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdft(): int
    {
        return $this->idft;
    }

    public function setIdft(int $idft): self
    {
        $this->idft = $idft;
        return $this;
    }

    public function getFecha(): DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(DateTimeInterface $fecha): self
    {
        $this->fecha = $fecha;
        return $this;
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

    public function getTipo(): int
    {
        return $this->tipo;
    }

    public function setTipo(int $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getIdfk(): int
    {
        return $this->idfk;
    }

    public function setIdfk(int $idfk): self
    {
        $this->idfk = $idfk;
        return $this;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }
}
