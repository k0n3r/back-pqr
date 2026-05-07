<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrFormRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrFormRepository::class)]
#[ORM\Table(name: 'pqr_forms')]
#[ORM\Index(columns: ['fk_formato'], name: 'ipqr_formsfk_format')]
#[ORM\Index(columns: ['fk_contador'], name: 'ipqr_formsfk_contad')]
class PqrForm
{
    public const string NOMBRE_REPORTE_PENDIENTE = 'rep_pendientes_pqr';
    public const string NOMBRE_REPORTE_PROCESO = 'rep_proceso_pqr';
    public const string NOMBRE_REPORTE_TERMINADO = 'rep_terminados_pqr';
    public const string NOMBRE_REPORTE_TODOS = 'rep_todos_pqr';
    public const string NOMBRE_REPORTE_POR_DEPENDENCIA = 'rep_dependencia_pqr';
    public const string NOMBRE_PANTALLA_GRAFICO = 'PQRSF';
    public const string FILTER_TODOS = 'dep_todos';
    public const string FILTER_PENDIENTES = 'dep_pendientes';
    public const string FILTER_RESUELTAS = 'dep_resueltas';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'fk_formato', type: 'integer', nullable: false)]
    private int $fkFormato;

    #[ORM\Column(name: 'fk_contador', type: 'integer', nullable: false)]
    private int $fkContador;

    #[ORM\Column(name: 'label', type: 'string', length: 255, nullable: false)]
    private string $label;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'show_anonymous', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $showAnonymous = false;

    #[ORM\Column(name: 'show_label', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $showLabel = true;

    #[ORM\Column(name: 'show_empty', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $showEmpty = true;

    #[ORM\Column(name: 'response_configuration', type: 'json', nullable: true)]
    private ?array $responseConfiguration = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $active = true;

    #[ORM\Column(name: 'fk_field_time', type: 'integer', nullable: false, options: ['default' => 0, 'comment' => 'idcampos_formato'])]
    private int $fkFieldTime = 0;

    #[ORM\Column(name: 'enable_filter_dep', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $enableFilterDep = false;

    #[ORM\Column(name: 'description_field', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $descriptionField = 0;

    #[ORM\Column(name: 'enable_balancer', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $enableBalancer = false;

    #[ORM\Column(name: 'enable_con_days', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $enableConDays = false;

    #[ORM\Column(name: 'fk_field_balancer', type: 'integer', nullable: false, options: ['default' => 0, 'comment' => 'idcampos_formato'])]
    private int $fkFieldBalancer = 0;

    #[ORM\Column(name: 'canal_recepcion', type: 'json', nullable: false)]
    private array $canalRecepcion = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getFkFormato(): int
    {
        return $this->fkFormato;
    }

    public function setFkFormato(int $fkFormato): self
    {
        $this->fkFormato = $fkFormato;
        return $this;
    }

    public function getFkContador(): int
    {
        return $this->fkContador;
    }

    public function setFkContador(int $fkContador): self
    {
        $this->fkContador = $fkContador;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isShowAnonymous(): bool
    {
        return $this->showAnonymous;
    }

    public function setShowAnonymous(bool $showAnonymous): self
    {
        $this->showAnonymous = $showAnonymous;
        return $this;
    }

    public function isShowLabel(): bool
    {
        return $this->showLabel;
    }

    public function setShowLabel(bool $showLabel): self
    {
        $this->showLabel = $showLabel;
        return $this;
    }

    public function isShowEmpty(): bool
    {
        return $this->showEmpty;
    }

    public function setShowEmpty(bool $showEmpty): self
    {
        $this->showEmpty = $showEmpty;
        return $this;
    }

    public function getResponseConfiguration(): ?array
    {
        return $this->responseConfiguration;
    }

    public function setResponseConfiguration(?array $responseConfiguration): self
    {
        $this->responseConfiguration = $responseConfiguration;
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

    public function getFkFieldTime(): int
    {
        return $this->fkFieldTime;
    }

    public function setFkFieldTime(int $fkFieldTime): self
    {
        $this->fkFieldTime = $fkFieldTime;
        return $this;
    }

    public function isEnableFilterDep(): bool
    {
        return $this->enableFilterDep;
    }

    public function setEnableFilterDep(bool $enableFilterDep): self
    {
        $this->enableFilterDep = $enableFilterDep;
        return $this;
    }

    public function getDescriptionField(): int
    {
        return $this->descriptionField;
    }

    public function setDescriptionField(int $descriptionField): self
    {
        $this->descriptionField = $descriptionField;
        return $this;
    }

    public function isEnableBalancer(): bool
    {
        return $this->enableBalancer;
    }

    public function setEnableBalancer(bool $enableBalancer): self
    {
        $this->enableBalancer = $enableBalancer;
        return $this;
    }

    public function isEnableConDays(): bool
    {
        return $this->enableConDays;
    }

    public function setEnableConDays(bool $enableConDays): self
    {
        $this->enableConDays = $enableConDays;
        return $this;
    }

    public function isEnabledCalendarDays(): bool
    {
        return $this->enableConDays;
    }

    public function getFkFieldBalancer(): int
    {
        return $this->fkFieldBalancer;
    }

    public function setFkFieldBalancer(int $fkFieldBalancer): self
    {
        $this->fkFieldBalancer = $fkFieldBalancer;
        return $this;
    }

    public function getCanalRecepcion(): array
    {
        return $this->canalRecepcion;
    }

    public function setCanalRecepcion(array $canalRecepcion): self
    {
        $this->canalRecepcion = $canalRecepcion;
        return $this;
    }

    /**
     * Retorna los datos en el mismo formato que el legacy PqrForm::getDataAttributes().
     */
    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'fk_formato'             => $this->fkFormato,
            'fk_contador'            => $this->fkContador,
            'show_anonymous'         => (int)$this->showAnonymous,
            'show_label'             => (int)$this->showLabel,
            'show_empty'             => (int)$this->showEmpty,
            'active'                 => (int)$this->active,
            'fk_field_time'          => $this->fkFieldTime,
            'enable_filter_dep'      => (int)$this->enableFilterDep,
            'description_field'      => $this->descriptionField,
            'enable_balancer'        => (int)$this->enableBalancer,
            'enable_con_days'        => (int)$this->enableConDays,
            'fk_field_balancer'      => $this->fkFieldBalancer,
            'label'                  => $this->label,
            'name'                   => $this->name,
            'response_configuration' => $this->responseConfiguration,
            'canal_recepcion'        => $this->canalRecepcion,
        ];
    }
}
