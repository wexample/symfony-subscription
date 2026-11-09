<?php

namespace Wexample\SymfonySubscription\Enum;

/**
 * Why an account was refused what it asked to consume.
 */
enum SubscriptionRefusalCause: string
{
    case NO_SUBSCRIPTION = 'no_subscription';
    case CREDIT_SPENT = 'credit_spent';
}
