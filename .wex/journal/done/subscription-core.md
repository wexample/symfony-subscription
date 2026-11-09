# Subscriptions: the right of an account to consume, its plan, its credit

Asked by Syrtis (`SYRTIS/local/api`), where a first version runs inside the
application (commit 16b0012). This package takes it over so that any
application can say what an account may consume and refuse it once spent.

## What Syrtis has today, to move here

- `src/Entity/Subscription.php`: an account (`user_id`, cascade on delete), a
  plan, `date_start`, `date_end` (null while running). Table `subscription`,
  migration `Version20261109190000`.
- `src/Enum/SubscriptionPlan.php`: one plan, `unlimited`, `getAllowance(): ?int`
  (null for no limit).
- `src/Repository/SubscriptionRepository.php`: `findRunning(User, at)`, the
  latest started among those not ended.
- `src/Service/Subscription/SubscriptionCreditService.php`:
  - `subscribe(User, plan)`: ends the running one, starts a new one now;
  - `findRunning(User)`;
  - `getRemaining(Subscription): ?int`: allowance less consumption;
  - `measureConsumption(Subscription): int`: Syrtis-specific (requests started
    by the account's api tokens since the start);
  - `check()`: reads the api token of the call, refuses with a 402 when no
    subscription runs (`no_subscription`) or none is left (`credit_spent`),
    journalled through symfony-api as `user_token.refused` with that cause.
- `src/Command/SubscriptionGrantCommand.php`: `app:subscription:grant <username> [plan]`.

## What the package should hold

- **A mapped superclass** `AbstractSubscription` (plan, dateStart, dateEnd), the
  application's subclass holding the relation to its account entity — as
  symfony-api does for tokens (`token_class` in its config). Syrtis's table and
  columns stay as they are: its entity becomes a subclass without a migration.
- **Plans given by the application**: an interface its backed enum implements
  (`getAllowance(): ?int` for now; a period — monthly, one-off — will come).
- **The measure given by the application**: a `ConsumptionMeterInterface`
  (`measure(AbstractSubscription): int`). The unit is not decided in Syrtis
  (requests or LLM tokens): the package must not choose it.
- **A service** for subscribe / findRunning / getRemaining, and a **check** for
  a given account that throws a refusal (402) carrying its cause. Not tied to
  api tokens: Syrtis calls it with the token's holder.
- **Its facts as security events** (an `AbstractSecurityEvent` subclass, as
  symfony-api's), and the subscriber writing them to the account's history
  when symfony-activity is installed — registered only then, as symfony-api's
  `ApiTokenActivitySubscriber`. Refusals merged with their repeats
  (fingerprint on type and cause).
- **The grant command**, generic.

## Later, not now

- Selling a plan through symfony-shop: a `CartItemPaidHandlerInterface`
  implementation that subscribes the buyer (the shop already names « renew a
  membership » as such a handler).
- Subscriptions held by something else than an account (a project): Syrtis
  keeps tokens per account for now.

## Once here

Syrtis replaces its classes with the package's, keeps its meter and its call to
the check on the two `session/continue` routes, and its tests
(`tests/Controller/Api/ApiRateLimitTest.php`) keep passing.
