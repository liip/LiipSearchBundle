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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $searchFactory = $this->loadSearchClients($container, $loader, $config);

        $container->setAlias(
            $this->getAlias().'.default_search_factory',
            $searchFactory
        );

        foreach (array('search_route', 'restrict_language', 'max_per_page') as $key) {
            $container->setParameter($this->getAlias().'.'.$key, $config[$key]);
        }

        $controllerOptions = array(
            'query_param_name' => $config['query_param_name'],
            'page_param_name' => $config['page_param_name'],
        );
        $container->setParameter($this->getAlias().'.controller.paged_search_options', $controllerOptions);

        $twigOptions = array(
            'query_param_name' => $config['query_param_name'],
            'search_route' => $config['search_route'],
        );
        $container->setParameter($this->getAlias().'.twig.searchbox_options', $twigOptions);
    }

    public function loadSearchClients(ContainerBuilder $container, XmlFileLoader $loader, array &$config)
    {
        $factory = $config['search_factory'];
        unset($config['search_factory']);
        $frontend = false;
        $backend = empty($factory);
        $controller = 'liip_search.controller.paged_search:searchAction';

        if ($config['clients']['google_rest']['enabled']) {
            foreach ($config['clients']['google_rest'] as $key => $value) {
                $container->setParameter($this->getAlias().'.google_rest.'.$key, $value);
            }
            $backend = true;
            $loader->load('google_rest.xml');
            if (empty($factory)) {
                $factory = 'liip_search.google_rest.factory';
            }
        }
        if ($config['clients']['google_cse']['enabled']) {
            $container->setParameter($this->getAlias().'.controller.frontend_search', array(
                'search_template' => 'LiipSearchBundle:google:search.html.twig',
                'box_template' => 'LiipSearchBundle:google:search_box.html.twig',
                'template_options' => array(
                    'google_custom_search_id', $config['clients']['google_cse']['cse_id'],
                ),
            ));
            $frontend = true;
            $loader->load('google_cse.xml');
            if (empty($factory)) {
                $controller = 'liip_search.controller.frontend_search:searchAction';
                $factory = 'liip_search.google_cse.factory';
            }
        }
        $container->setParameter('liip_search.controller.search_action', $controller);
        unset($config['clients']);

        if ($backend) {
            $loader->load('backend_search.xml');
        }
        if ($frontend) {
            $loader->load('frontend_search.xml');
        }

        return $factory;
    }
}
