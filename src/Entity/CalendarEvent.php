<?php

namespace App\Entity;

use App\Repository\CalendarEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CalendarEventRepository::class)]
#[ORM\Table(name: 'calendar_events')]
class CalendarEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Habit::class, inversedBy: 'calendarEvents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Habit $habit = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(
        propertyPath: 'startDate',
        message: 'The end date must be after the start date.'
    )]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $externalSource = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recurrenceRule = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $recurrenceEndDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHabit(): ?Habit
    {
        return $this->habit;
    }

    public function setHabit(?Habit $habit): self
    {
        $this->habit = $habit;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getExternalSource(): ?string
    {
        return $this->externalSource;
    }

    public function setExternalSource(?string $externalSource): self
    {
        $this->externalSource = $externalSource;
        return $this;
    }

    public function getRecurrenceRule(): ?string
    {
        return $this->recurrenceRule;
    }

    public function setRecurrenceRule(?string $recurrenceRule): self
    {
        $this->recurrenceRule = $recurrenceRule;
        return $this;
    }

    public function getRecurrenceEndDate(): ?\DateTimeImmutable
    {
        return $this->recurrenceEndDate;
    }

    public function setRecurrenceEndDate(?\DateTimeImmutable $recurrenceEndDate): self
    {
        $this->recurrenceEndDate = $recurrenceEndDate;
        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->recurrenceRule !== null;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
