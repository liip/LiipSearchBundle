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
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
* This class contains the configuration information for the bundle
*
* This information is solely responsible for how the different configuration
* sections are normalized, and merged.
*
* @author David Buchmann
*/
class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('liip_search')
            ->children()
                ->arrayNode('google')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('search_key')->defaultFalse()->end()
                        ->scalarNode('restrict_to_site')->defaultValue('')->end()
                        ->scalarNode('restrict_to_labels')->defaultValue('')->end()
                    ->end()
                ->end()
                ->scalarNode('search_route')->defaultValue('liip_search')->end()
                ->scalarNode('pager_max_head_items')->defaultValue(2)->end()
                ->scalarNode('pager_max_tail_items')->defaultValue(2)->end()
                ->scalarNode('pager_max_adjoining_items')->defaultValue(2)->end()
                ->scalarNode('results_per_page')->defaultValue(10)->end()
                ->scalarNode('restrict_by_language')->defaultFalse()->end()
                ->scalarNode('translation_domain')->defaultValue('LiipSearchBundle_search')->end()
                ->scalarNode('query_param_name')->defaultValue('query')->end()
                ->scalarNode('page_param_name')->defaultValue('page')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
