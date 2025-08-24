<?php

namespace App\Entity;

use App\Repository\HabitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HabitRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Habit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Habit name must be at least {{ limit }} characters long',
        maxMessage: 'Habit name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\Positive]
    private int $targetFrequency = 1; // must be positive (e.g. times per week)

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'habit', targetEntity: HabitCompletion::class, orphanRemoval: true)]
    private Collection $completions;

    #[ORM\OneToMany(mappedBy: 'habit', targetEntity: CalendarEvent::class, orphanRemoval: true)]
    private Collection $calendarEvents;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'habits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->completions = new ArrayCollection();
        $this->calendarEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getTargetFrequency(): int
    {
        return $this->targetFrequency;
    }

    public function setTargetFrequency(int $targetFrequency): self
    {
        if ($targetFrequency <= 0) {
            throw new \InvalidArgumentException('Target frequency must be positive');
        }
        $this->targetFrequency = $targetFrequency;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, HabitCompletion>
     */
    public function getCompletions(): Collection
    {
        return $this->completions;
    }

    public function addCompletion(HabitCompletion $completion): self
    {
        if (!$this->completions->contains($completion)) {
            $this->completions[] = $completion;
            $completion->setHabit($this);
        }

        return $this;
    }

    public function removeCompletion(HabitCompletion $completion): self
    {
        if ($this->completions->removeElement($completion)) {
            // set the owning side to null (unless already changed)
            if ($completion->getHabit() === $this) {
                $completion->setHabit(null);
            }
        }

        return $this;
    }
}
