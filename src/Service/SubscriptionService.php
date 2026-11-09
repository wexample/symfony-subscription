<?php

namespace Wexample\SymfonySubscription\Service;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonySecurity\Helper\RequestIdHelper;
use Wexample\SymfonySubscription\Class\SubscriptionPlan;
use Wexample\SymfonySubscription\Entity\Subscription;
use Wexample\SymfonySubscription\Enum\SubscriptionRefusalCause;
use Wexample\SymfonySubscription\Enum\SubscriptionSecurityEventType;
use Wexample\SymfonySubscription\Event\SubscriptionSecurityEvent;
use Wexample\SymfonySubscription\Exception\SubscriptionRefusedException;
use Wexample\SymfonySubscription\Interface\ConsumptionMeterInterface;
use Wexample\SymfonySubscription\Repository\SubscriptionRepository;

/**
 * What an account may consume: its running subscription's plan, less what the
 * application's meter says it consumed since the subscription started. The one
 * place an account is put on a plan, and the one place it is let through or
 * refused for it.
 */
class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $repository,
        private readonly SubscriptionPlanRegistry $plans,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack,
        private readonly ?ConsumptionMeterInterface $meter = null,
    ) {
    }

    /**
     * $user on the plan named $plan from now: the subscription running, if
     * any, ends there.
     */
    public function subscribe(UserEntityInterface $user, string $plan): Subscription
    {
        $this->plans->get($plan);
        $now = new DateTimeImmutable();
        $this->findRunning($user)?->setDateEnd($now);

        $subscription = new Subscription($user, $plan, $now);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->record(SubscriptionSecurityEventType::STARTED, $user, $plan);

        return $subscription;
    }

    public function findRunning(UserEntityInterface $user): ?Subscription
    {
        return $this->repository->findRunning($user, new DateTimeImmutable());
    }

    public function getPlan(Subscription $subscription): SubscriptionPlan
    {
        return $this->plans->get($subscription->getPlan());
    }

    /**
     * What $subscription may still consume; null for no limit.
     *
     * @throws LogicException when its plan is limited and the application has
     *                        no meter to say what was consumed
     */
    public function getRemaining(Subscription $subscription): ?int
    {
        $plan = $this->getPlan($subscription);

        if ($plan->isUnlimited()) {
            return null;
        }

        if (! $this->meter) {
            throw new LogicException(sprintf('The plan "%s" is limited: implement %s to measure what is consumed.', $plan->name, ConsumptionMeterInterface::class));
        }

        return max(0, $plan->allowance - $this->meter->measure($subscription));
    }

    /**
     * @throws SubscriptionRefusedException when $user has no subscription
     *                                      running, or none left to consume
     */
    public function check(UserEntityInterface $user): void
    {
        $subscription = $this->findRunning($user);

        if (! $subscription) {
            $this->refuse($user, null, SubscriptionRefusalCause::NO_SUBSCRIPTION);
        }

        if (0 === $this->getRemaining($subscription)) {
            $this->refuse($user, $subscription->getPlan(), SubscriptionRefusalCause::CREDIT_SPENT);
        }
    }

    private function refuse(UserEntityInterface $user, ?string $plan, SubscriptionRefusalCause $cause): never
    {
        $this->record(SubscriptionSecurityEventType::REFUSED, $user, $plan, $cause->value);

        throw new SubscriptionRefusedException($cause);
    }

    private function record(
        SubscriptionSecurityEventType $type,
        UserEntityInterface $user,
        ?string $plan,
        ?string $cause = null,
    ): void {
        $request = $this->requestStack->getMainRequest();

        $this->eventDispatcher->dispatch(new SubscriptionSecurityEvent(
            type: $type,
            occurredAt: new DateTimeImmutable('now', new DateTimeZone('UTC')),
            userId: (string) $user->getId(),
            cause: $cause,
            ip: $request?->getClientIp(),
            userAgent: $request?->headers->get('User-Agent'),
            requestId: RequestIdHelper::resolve($request),
            extra: [
                'user_type' => ClassHelper::getRealClassPath($user),
                'plan' => $plan,
            ],
        ));
    }
}
