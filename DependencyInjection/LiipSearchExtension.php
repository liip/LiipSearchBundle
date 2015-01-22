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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

class LiipSearchExtension extends Extension
{
    /**
     * Loads the services based on your application configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        foreach ($config['pager'] as $key => $value) {
            $container->setParameter($this->getAlias().'.pager.'.$key, $value);
            unset($config['pager']);
        }

        $search_client = $this->loadSearchClients($container, $loader, $config);

        $container->setAlias(
            $this->getAlias().'.default_search_client',
            $search_client
        );

        $container->setParameter($this->getAlias().'.controller.options', $config);
        $container->setParameter($this->getAlias().'.search_route', $config['search_route']);
        $loader->load('services.yml');
    }

    public function loadSearchClients(ContainerBuilder $container, YamlFileLoader $loader, array &$config)
    {
        $client = $config['search_client'];
        unset($config['search_client']);

        if ($config['clients']['google_rest']['enabled']) {
            foreach ($config['clients']['google_rest'] as $key => $value) {
                $container->setParameter($this->getAlias().'.google_rest.'.$key, $value);
            }

            $loader->load('google_rest.yml');
            if (empty($client)) {
                $client = 'liip_search.search.google_rest_api';
            }
        }
        if ($config['clients']['google_cse']['enabled']) {
            $config['options'] = array(
                'search_template' => 'LiipSearchBundle:google:search.html.twig',
                'box_template' => 'LiipSearchBundle:google:search_box.html.twig',
                'template_options' => array(
                    'google_custom_search_id', $config['clients']['google_cse']['cse_id'],
                ),
            );
            $loader->load('google_cse.yml');
            if (empty($client)) {
                $client = 'liip_search.search.google_cse';
            }
        }
        unset($config['clients']);

        return $client;
    }
}
