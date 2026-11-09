<?php

namespace Wexample\SymfonySubscription\Service;

use InvalidArgumentException;
use Wexample\SymfonySubscription\Class\SubscriptionPlan;

/**
 * The plans the application declares (`wexample_symfony_subscription.plans`).
 */
class SubscriptionPlanRegistry
{
    /**
     * @param array<string, array{allowance: ?int}> $plans
     */
    public function __construct(
        private readonly array $plans,
    ) {
    }

    /**
     * @return list<SubscriptionPlan>
     */
    public function all(): array
    {
        return array_map($this->get(...), array_keys($this->plans));
    }

    public function has(string $name): bool
    {
        return isset($this->plans[$name]);
    }

    /**
     * @throws InvalidArgumentException when the application declares no such plan
     */
    public function get(string $name): SubscriptionPlan
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('No subscription plan named "%s".', $name));
        }

        return new SubscriptionPlan($name, $this->plans[$name]['allowance']);
    }
}
