<?php

namespace Wexample\SymfonySubscription\Event;

use Wexample\SymfonySecurity\Event\AbstractSecurityEvent;
use Wexample\SymfonySubscription\Enum\SubscriptionSecurityEventType;

/**
 * A subscription fact. `user_id` holds the account, `extra.user_type` its
 * class, `extra.plan` the plan concerned.
 *
 * @property-read SubscriptionSecurityEventType $type
 */
class SubscriptionSecurityEvent extends AbstractSecurityEvent
{
    public function __construct(SubscriptionSecurityEventType $type, mixed ...$arguments)
    {
        parent::__construct($type, ...$arguments);
    }
}
