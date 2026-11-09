<?php

namespace Wexample\SymfonySubscription\Interface;

use Wexample\SymfonySubscription\Entity\Subscription;

/**
 * What an account consumed under a subscription, in the unit its plans are
 * counted in — requests, tokens of a model, documents —: the application's to
 * say, as the one service implementing this. Without one, only unlimited
 * plans can be used.
 */
interface ConsumptionMeterInterface
{
    public function measure(Subscription $subscription): int;
}
