<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Entity;

use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PqrNotyMessageRepository::class)]
#[ORM\Table(name: 'pqr_noty_messages')]
class PqrNotyMessage
{
    public const int TYPE_NOTY = 1;
    public const int TYPE_EMAIL = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'label', type: 'string', length: 50, nullable: false)]
    private string $label;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'subject', type: 'text', nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(name: 'message_body', type: 'text', nullable: true)]
    private ?string $messageBody = null;

    #[ORM\Column(name: 'type', type: 'integer', nullable: false, options: ['default' => 1, 'comment' => '1:Noty,2:Email'])]
    private int $type = self::TYPE_NOTY;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false, options: ['default' => 1])]
    private bool $active = true;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessageBody(): ?string
    {
        return $this->messageBody;
    }

    public function setMessageBody(?string $messageBody): self
    {
        $this->messageBody = $messageBody;
        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;
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
