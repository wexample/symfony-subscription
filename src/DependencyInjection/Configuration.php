<?php

namespace Wexample\SymfonySubscription\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_subscription');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('plans')
                    ->info('The plans an account can be put on, by name: what each lets it consume, in the unit the application\'s meter counts (ConsumptionMeterInterface).')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('allowance')
                                ->info('How much a subscription on this plan may consume since it started. Unset, no limit.')
                                ->defaultNull()
                                ->min(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_plan')
                    ->info('The plan `subscription:grant` puts an account on when none is named.')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
