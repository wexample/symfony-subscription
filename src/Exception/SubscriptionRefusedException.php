<?php

namespace Wexample\SymfonySubscription\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Wexample\SymfonySubscription\Enum\SubscriptionRefusalCause;

/**
 * An account refused what it asked to consume: a 402, wherever it is thrown.
 */
class SubscriptionRefusedException extends HttpException
{
    public function __construct(public readonly SubscriptionRefusalCause $refusalCause)
    {
        parent::__construct(
            Response::HTTP_PAYMENT_REQUIRED,
            match ($refusalCause) {
                SubscriptionRefusalCause::NO_SUBSCRIPTION => 'No subscription running.',
                SubscriptionRefusalCause::CREDIT_SPENT => 'Subscription credit spent.',
            }
        );
    }
}
