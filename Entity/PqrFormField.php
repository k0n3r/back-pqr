<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrFormFieldRepository::class)]
#[ORM\Table(name: 'pqr_form_fields')]
#[ORM\Index(columns: ['fk_pqr_html_field'], name: 'i_fk_pqr_html_field')]
#[ORM\Index(columns: ['fk_pqr_form'], name: 'i_fk_pqr_form')]
#[ORM\Index(columns: ['fk_campos_formato'], name: 'i_fk_campos_formato')]
class PqrFormField
{
    public const int ACTIVE = 1;
    public const int INACTIVE = 0;

    public const string FIELD_NAME_SYS_TIPO = 'sys_tipo';
    public const string FIELD_NAME_SYS_DEPENDENCIA = 'sys_dependencia';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'label', type: 'string', length: 400, nullable: false)]
    private string $label;

    #[ORM\Column(name: 'required', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $required = false;

    #[ORM\Column(name: 'anonymous', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $anonymous = false;

    #[ORM\Column(name: 'show_report', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $showReport = false;

    #[ORM\Column(name: 'required_anonymous', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $requiredAnonymous = false;

    #[ORM\Column(name: 'setting', type: 'text', nullable: false)]
    private string $setting;

    #[ORM\ManyToOne(targetEntity: PqrHtmlField::class)]
    #[ORM\JoinColumn(name: 'fk_pqr_html_field', referencedColumnName: 'id', nullable: false)]
    private PqrHtmlField $htmlField;

    #[ORM\ManyToOne(targetEntity: PqrForm::class)]
    #[ORM\JoinColumn(name: 'fk_pqr_form', referencedColumnName: 'id', nullable: false)]
    private PqrForm $pqrForm;

    #[ORM\Column(name: 'fk_campos_formato', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $fkCamposFormato = 0;

    #[ORM\Column(name: 'is_system', type: 'boolean', nullable: true, options: ['default' => 0])]
    private ?bool $isSystem = false;

    #[ORM\Column(name: 'orden', type: 'integer', nullable: true, options: ['default' => 0])]
    private ?int $orden = 0;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: true, options: ['default' => 1])]
    private ?bool $active = true;

    public function getId(): int
    {
        return $this->id;
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function isRequired(): bool
    {
        return (bool)$this->required;
    }

    public function setRequired(?bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function isAnonymous(): bool
    {
        return (bool)$this->anonymous;
    }

    public function setAnonymous(?bool $anonymous): self
    {
        $this->anonymous = $anonymous;
        return $this;
    }

    public function isShowReport(): bool
    {
        return (bool)$this->showReport;
    }

    public function setShowReport(?bool $showReport): self
    {
        $this->showReport = $showReport;
        return $this;
    }

    public function isRequiredAnonymous(): bool
    {
        return (bool)$this->requiredAnonymous;
    }

    public function setRequiredAnonymous(?bool $requiredAnonymous): self
    {
        $this->requiredAnonymous = $requiredAnonymous;
        return $this;
    }

    public function getSetting(): string
    {
        return $this->setting;
    }

    public function setSetting(string $setting): self
    {
        $this->setting = $setting;
        return $this;
    }

    public function getSettingDecoded(): ?object
    {
        return json_decode($this->setting);
    }

    public function getHtmlField(): PqrHtmlField
    {
        return $this->htmlField;
    }

    public function setHtmlField(PqrHtmlField $htmlField): self
    {
        $this->htmlField = $htmlField;
        return $this;
    }

    public function getFkPqrHtmlField(): int
    {
        return $this->htmlField->getId();
    }

    public function getPqrForm(): PqrForm
    {
        return $this->pqrForm;
    }

    public function setPqrForm(PqrForm $pqrForm): self
    {
        $this->pqrForm = $pqrForm;
        return $this;
    }

    public function getFkPqrForm(): int
    {
        return $this->pqrForm->getId();
    }

    public function getFkCamposFormato(): int
    {
        return $this->fkCamposFormato ?? 0;
    }

    public function setFkCamposFormato(?int $fkCamposFormato): self
    {
        $this->fkCamposFormato = $fkCamposFormato;
        return $this;
    }

    public function isSystem(): bool
    {
        return (bool)$this->isSystem;
    }

    public function setIsSystem(?bool $isSystem): self
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    public function getOrden(): int
    {
        return $this->orden ?? 0;
    }

    public function setOrden(?int $orden): self
    {
        $this->orden = $orden;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active === true || $this->active === 1;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getDataAttributes(): array
    {
        return [
            'id'                 => $this->id ?? 0,
            'name'               => $this->name,
            'label'              => $this->label,
            'required'           => (int)$this->isRequired(),
            'anonymous'          => (int)$this->isAnonymous(),
            'show_report'        => (int)$this->isShowReport(),
            'required_anonymous' => (int)$this->isRequiredAnonymous(),
            'setting'            => $this->setting,
            'fk_pqr_html_field'  => [
                'id'        => $this->htmlField->getId(),
                'uniq'      => (int)$this->htmlField->isUniq(),
                'active'    => (int)$this->htmlField->isActive(),
                'label'     => $this->htmlField->getLabel(),
                'type'      => $this->htmlField->getType(),
                'type_saia' => $this->htmlField->getTypeSaia(),
            ],
            'fk_pqr_form'        => $this->getFkPqrForm(),
            'fk_campos_formato'  => $this->getFkCamposFormato(),
            'is_system'          => (int)$this->isSystem(),
            'orden'              => $this->getOrden(),
            'active'             => (int)$this->isActive(),
        ];
    }
}
