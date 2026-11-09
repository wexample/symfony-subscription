<?php

namespace Wexample\SymfonySubscription\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonySubscription\Entity\Subscription;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * The subscription of $user running at $at: started, not ended — the
     * latest started when several overlap.
     */
    public function findRunning(UserEntityInterface $user, DateTimeImmutable $at): ?Subscription
    {
        return $this->createQueryBuilder('subscription')
            ->where('subscription.user = :user')
            ->andWhere('subscription.dateStart <= :at')
            ->andWhere('subscription.dateEnd IS NULL OR subscription.dateEnd > :at')
            ->setParameter('user', $user)
            ->setParameter('at', $at)
            ->orderBy('subscription.dateStart', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Every subscription $user has had, the latest first.
     *
     * @return list<Subscription>
     */
    public function findOfUser(UserEntityInterface $user): array
    {
        return $this->findBy(['user' => $user], ['dateStart' => 'DESC']);
    }
}
