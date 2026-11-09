<?php

namespace Wexample\SymfonySubscription\Class;

/**
 * A plan as the application declares it (`wexample_symfony_subscription.plans`).
 */
final readonly class SubscriptionPlan
{
    /**
     * @param int|null $allowance how much a subscription on it may consume since
     *                            it started, in the meter's unit; null for no limit
     */
    public function __construct(
        public string $name,
        public ?int $allowance,
    ) {
    }

    public function isUnlimited(): bool
    {
        return null === $this->allowance;
    }
}
