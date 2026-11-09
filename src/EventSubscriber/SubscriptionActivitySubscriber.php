<?php

namespace Wexample\SymfonySubscription\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Wexample\SymfonyActivity\Class\ActivitySubject;
use Wexample\SymfonyActivity\Enum\ActivityCategory;
use Wexample\SymfonyActivity\Service\ActivityRecorder;
use Wexample\SymfonySubscription\Event\SubscriptionSecurityEvent;

/**
 * Every subscription fact, written to the account's history in the `security`
 * category of symfony-activity. A refusal is merged with its repeats of the
 * same cause (`merge_within`): a client calling on with no credit left is one
 * entry counting, not a row per call.
 *
 * Registered only when the symfony-activity bundle is enabled, and recording
 * nothing until the application enables `security` there.
 */
class SubscriptionActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActivityRecorder $recorder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [SubscriptionSecurityEvent::class => 'onSecurityEvent'];
    }

    public function onSecurityEvent(SubscriptionSecurityEvent $event): void
    {
        if (! $this->recorder->records(ActivityCategory::SECURITY->value)) {
            return;
        }

        $this->recorder->record(
            new ActivitySubject($event->extra['user_type'], $event->userId),
            ActivityCategory::SECURITY->value,
            ActivityCategory::SECURITY->value . '.' . $event->type->value,
            array_filter([
                'cause' => $event->cause,
                'plan' => $event->extra['plan'] ?? null,
                'ip' => $event->ip,
                'user_agent' => $event->userAgent,
            ], static fn ($value) => null !== $value),
            fingerprint: $event->type->isFailure()
                ? hash('sha256', $event->type->value . '|' . $event->cause)
                : null,
        );
    }
}
