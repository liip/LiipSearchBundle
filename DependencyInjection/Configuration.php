<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * This class contains the configuration information for the bundle.
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
            ->children()
                ->scalarNode('search_factory')->defaultNull()->end()
                ->scalarNode('search_route')->defaultValue('liip_search')->end()
                ->scalarNode('query_param_name')->defaultValue('query')->end()
                ->scalarNode('page_param_name')->defaultValue('page')->end()
                ->scalarNode('restrict_language')->defaultFalse()->end()
                ->scalarNode('max_per_page')->defaultValue(10)->end()
                ->arrayNode('clients')
                    ->canBeUnset()
                    ->children()
                        ->arrayNode('google_rest')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                                ->arrayNode('search_key')
                                    ->isRequired()
                                    ->beforeNormalization()
                                        ->ifTrue(function ($v) {return is_string($v) || is_int($v); })
                                        ->then(function ($v) {return array('%kernel.default_locale%' => $v); })
                                    ->end()
                                    ->useAttributeAsKey('locale')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->scalarNode('search_api_url')->defaultValue('https://www.googleapis.com/customsearch/v1')->end()
                                ->scalarNode('restrict_to_site')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('google_cse')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->canBeEnabled()
                            ->children()
                                ->arrayNode('cse_id')
                                    ->isRequired()
                                    ->beforeNormalization()
                                        ->ifTrue(function ($v) {return is_string($v) || is_int($v); })
                                        ->then(function ($v) {return array('%kernel.default_locale%' => $v); })
                                    ->end()
                                    ->useAttributeAsKey('locale')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
