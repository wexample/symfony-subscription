<?php

namespace Wexample\SymfonySubscription\Enum;

use Wexample\SymfonySecurity\Interface\SecurityEventTypeInterface;

/**
 * Every fact of a subscription the package records: an account put on a plan,
 * an account refused for lack of one or of credit — the cause in
 * SubscriptionSecurityEvent::$cause.
 */
enum SubscriptionSecurityEventType: string implements SecurityEventTypeInterface
{
    case STARTED = 'subscription.started';
    case REFUSED = 'subscription.refused';

    public function isFailure(): bool
    {
        return self::REFUSED === $this;
    }
}
