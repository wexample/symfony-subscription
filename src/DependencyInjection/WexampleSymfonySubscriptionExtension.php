<?php

namespace Wexample\SymfonySubscription\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyActivity\WexampleSymfonyActivityBundle;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonySubscription\EventSubscriber\SubscriptionActivitySubscriber;

class WexampleSymfonySubscriptionExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('wexample_symfony_subscription.plans', $config['plans']);
        $container->setParameter('wexample_symfony_subscription.default_plan', $config['default_plan']);

        $this->loadConfig(
            __DIR__,
            $container
        );

        // The subscription facts in the accounts' histories, where there are histories.
        if (in_array(WexampleSymfonyActivityBundle::class, $container->getParameter('kernel.bundles'), true)) {
            $container->autowire(SubscriptionActivitySubscriber::class)->setAutoconfigured(true);
        }
    }
}
