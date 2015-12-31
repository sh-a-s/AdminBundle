<?php

namespace ITF\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('itf_admin');

        $rootNode
            ->children()
                ->scalarNode('title')->end()
                ->scalarNode('frontend_route')->end()
                ->arrayNode('bundles')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('entities')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('title')->defaultFalse()->end()
                                        ->scalarNode('icon')->defaultFalse()->end()
                                        ->booleanNode('add_allowed')->defaultTrue()->end()
                                        ->arrayNode('template')
	                                        ->children()
										        ->scalarNode('new')->defaultFalse()->end()
										        ->scalarNode('edit')->defaultFalse()->end()
	                                        ->end()
	                                    ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('dashboard_service')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
