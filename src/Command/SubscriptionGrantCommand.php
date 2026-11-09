<?php

namespace Wexample\SymfonySubscription\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonySubscription\Service\SubscriptionPlanRegistry;
use Wexample\SymfonySubscription\Service\SubscriptionService;

/**
 * An account put on a plan from now, the one it was on ended there. The
 * account is found as it signs in, by its identifier.
 */
#[AsCommand(name: 'subscription:grant', description: 'Put an account on a subscription plan from now')]
class SubscriptionGrantCommand extends Command
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionPlanRegistry $plans,
        #[Autowire(service: 'security.user_providers')]
        private readonly UserProviderInterface $userProvider,
        #[Autowire(param: 'wexample_symfony_subscription.default_plan')]
        private readonly ?string $defaultPlan,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('identifier', InputArgument::REQUIRED, 'The account, as it signs in')
            ->addArgument('plan', InputArgument::OPTIONAL, 'The plan, the default one if none');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plan = $input->getArgument('plan') ?? $this->defaultPlan;

        if (null === $plan || ! $this->plans->has($plan)) {
            $io->error(sprintf('Name one of the plans: %s.', implode(', ', array_map(static fn ($plan) => $plan->name, $this->plans->all()))));

            return Command::FAILURE;
        }

        try {
            $user = $this->userProvider->loadUserByIdentifier($input->getArgument('identifier'));
        } catch (UserNotFoundException) {
            $io->error('Unknown account.');

            return Command::FAILURE;
        }

        if (! $user instanceof UserEntityInterface) {
            $io->error(sprintf('The account is no %s.', UserEntityInterface::class));

            return Command::FAILURE;
        }

        $this->subscriptionService->subscribe($user, $plan);
        $io->success(sprintf('%s is on the %s plan from now.', $user->getUserIdentifier(), $plan));

        return Command::SUCCESS;
    }
}
