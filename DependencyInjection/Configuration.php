<?php

namespace Lilweb\LdapBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lilweb_ldap');
        $rootNode
            ->children()
                ->arrayNode('client')
                ->children()
                    ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('port')->defaultValue(389)->end()
                    ->scalarNode('version')->end()
                    ->scalarNode('username')->end()
                    ->scalarNode('password')->end()
                    ->scalarNode('referrals_enabled')->end()
                ->end()
            ->end()
            ->arrayNode('user')
                ->children()
                    ->scalarNode('base_dn')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('filter')->end()
                    ->scalarNode('name_attribute')->defaultValue('uid')->end()
                    ->variableNode('attributes')->defaultValue(array())->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
