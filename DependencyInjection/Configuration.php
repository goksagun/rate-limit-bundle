<?php

namespace Goksagun\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rate_limit');

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
            ->end()
            ->children()
                ->booleanNode('display_headers')->defaultTrue()->end()
            ->end()
            ->children()
                ->booleanNode('display_reset_date')->defaultFalse()->end()
            ->end()
            ->children()
                ->scalarNode('response_message')->defaultValue('Rate limit exceeded')->end()
            ->end()
            ->children()
                ->integerNode('response_status_code')->defaultValue(429)->end()
            ->end()
            ->children()
                ->scalarNode('response_exception')->defaultNull()->end()
            ->end()
            ->children()
                ->arrayNode('headers')
                    ->addDefaultsIfNotSet()
                    ->info('What are the different header names to add')
                    ->children()
                        ->scalarNode('limit')->defaultValue('X-RateLimit-Limit')->end()
                        ->scalarNode('remaining')->defaultValue('X-RateLimit-Remaining')->end()
                        ->scalarNode('reset')->defaultValue('X-RateLimit-Reset')->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('paths')
                    ->defaultValue([])
                    ->info('Rate limit paths')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('path')->isRequired()->end()
                            ->arrayNode('methods')
                                ->enumPrototype()
                                    ->values(['*', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'])
                                ->end()
                                ->defaultValue(['*'])
                                ->requiresAtLeastOneElement()
                            ->end()
                            ->integerNode('limit')->isRequired()->min(0)->end()
                            ->integerNode('period')->isRequired()->min(0)->end()
                            ->integerNode('increment')->defaultValue(null)->min(0)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
