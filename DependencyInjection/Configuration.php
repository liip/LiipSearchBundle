<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return NodeInterface
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('liip_search')
            ->validate()
                ->ifTrue(function ($v) {
                    return !$v['clients']['google_rest']['enabled']
                        && !$v['clients']['google_cse']['enabled']
                        && empty($v['search_client']);
                })
                ->then(function ($v) {
                    throw new InvalidConfigurationException('You need to configure the google API client or specify a search_client service.');
                })
            ->end()
            ->children()
                ->scalarNode('search_client')->defaultNull()->end()
                ->scalarNode('search_route')->defaultValue('liip_search')->end()
                ->scalarNode('restrict_language')->defaultFalse()->end()
                ->arrayNode('pager')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('results_per_page')->defaultValue(10)->end()
                        ->scalarNode('max_head_items')->defaultValue(2)->end()
                        ->scalarNode('max_tail_items')->defaultValue(2)->end()
                        ->scalarNode('max_adjoining_items')->defaultValue(2)->end()
                    ->end()
                ->end()
                ->arrayNode('clients')
                    ->canBeUnset()
                    ->children()
                        ->arrayNode('google_rest')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                                ->scalarNode('search_key')->isRequired()->end()
                                ->scalarNode('search_api_url')->defaultValue('https://www.googleapis.com/customsearch/v1')->end()
                                ->scalarNode('restrict_to_site')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('google_cse')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('cse_id')->isRequired()->end()
                            ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
