<?php

namespace Wexample\SymfonySubscription\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonySubscription\Repository\SubscriptionRepository;

/**
 * An account on a plan, from a date and until another once it ends. The
 * account is the application's user entity, through
 * `doctrine.orm.resolve_target_entities` (UserEntityInterface).
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscription')]
#[ORM\Index(name: 'subscription_user_start', columns: ['user_id', 'date_start'])]
class Subscription extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: UserEntityInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private UserEntityInterface $user;

    // A plan's name among the application's (SubscriptionPlanRegistry).
    #[ORM\Column(length: 30)]
    private string $plan;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $dateStart;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $dateEnd = null;

    public function __construct(UserEntityInterface $user, string $plan, DateTimeImmutable $dateStart)
    {
        parent::__construct();
        $this->user = $user;
        $this->plan = $plan;
        $this->dateStart = $dateStart;
    }

    public function getUser(): UserEntityInterface
    {
        return $this->user;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function getDateStart(): DateTimeImmutable
    {
        return $this->dateStart;
    }

    public function getDateEnd(): ?DateTimeImmutable
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?DateTimeImmutable $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }
}
