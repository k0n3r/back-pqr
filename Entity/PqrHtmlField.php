<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrHtmlFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrHtmlFieldRepository::class)]
#[ORM\Table(name: 'pqr_html_fields')]
class PqrHtmlField
{
    public const string TYPE_DEPENDENCIA = 'dependencia';
    public const string TYPE_LOCALIDAD = 'localidad';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'label', type: 'string', length: 50, nullable: false)]
    private string $label;

    #[ORM\Column(name: 'type', type: 'string', length: 50, nullable: false)]
    private string $type;

    #[ORM\Column(name: 'type_saia', type: 'string', length: 50, nullable: false)]
    private string $typeSaia;

    #[ORM\Column(name: 'uniq', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $uniq = false;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $active = true;

    public function getId(): int
    {
        return $this->id;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeSaia(): string
    {
        return $this->typeSaia;
    }

    public function setTypeSaia(string $typeSaia): self
    {
        $this->typeSaia = $typeSaia;
        return $this;
    }

    public function isUniq(): bool
    {
        return $this->uniq;
    }

    public function setUniq(bool $uniq): self
    {
        $this->uniq = $uniq;
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

    public function isValidFieldForResponseDaysOrBalance(): bool
    {
        return in_array($this->typeSaia, ['Select', 'Radio'], true);
    }

    public function isValidForOptions(): bool
    {
        return in_array($this->typeSaia, ['Select', 'Radio', 'Checkbox'], true);
    }
}
