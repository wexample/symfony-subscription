## What it keeps

An account on a plan, from a date and until another once it ends: one `Entity\Subscription` row per period, the running one the latest started and not ended. The account is the application's user entity, reached through `UserEntityInterface` (symfony-helpers):

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        resolve_target_entities:
            Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface: App\Entity\User
```

The bundle maps its `subscription` table: generate a migration after installing.

## Plans

The application declares them, by name, with what each lets an account consume. Unset, no limit:

```yaml
# config/packages/wexample_symfony_subscription.yaml
wexample_symfony_subscription:
    plans:
        unlimited: ~
        starter: { allowance: 1000 }
    default_plan: unlimited      # what subscription:grant uses when none is named
```

## What is consumed

The package does not decide the unit — requests, tokens of a model, documents —: the application does, as the one service implementing `ConsumptionMeterInterface`, aliased to it:

```yaml
# config/services.yaml
Wexample\SymfonySubscription\Interface\ConsumptionMeterInterface: '@App\Service\Subscription\RequestConsumptionMeter'
```

`measure(Subscription)` returns what the account consumed since the subscription started. Without a meter, only unlimited plans can be used: a limited one throws when its credit is read.

## Letting through, refusing

```php
$subscriptionService->check($user);          // SubscriptionRefusedException, a 402, when refused
$subscriptionService->subscribe($user, 'starter');  // from now, the running one ended
$subscriptionService->findRunning($user);
$subscriptionService->getRemaining($subscription);  // null for no limit
```

The application calls `check()` where an account starts consuming — before starting the work, not on every page. A refusal carries its cause, `no_subscription` or `credit_spent`.

An account with no subscription running is refused: give one to every existing account in the migration that installs the package, and to each new one.

```bash
bin/console subscription:grant <identifier> [plan]
```

The account is found as it signs in, through the security user providers.

## History

Each fact — `subscription.started`, `subscription.refused` — is dispatched as a `SubscriptionSecurityEvent`. With symfony-activity installed and its `security` category enabled, it goes to the account's history; refusals of one cause are merged with their repeats under the category's `merge_within`, so that a client calling on with no credit left is one entry counting.
