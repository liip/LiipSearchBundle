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
        $loader->load('twig_extension.xml');

        $searchFactory = $this->loadSearchClients($container, $loader, $config);

        if ($searchFactory) {
            $container->setAlias(
                $this->getAlias().'.default_search_factory',
                $searchFactory
            );
        }

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

        if (!empty($config['clients']['google_rest']['enabled'])) {
            foreach ($config['clients']['google_rest'] as $key => $value) {
                $container->setParameter($this->getAlias().'.google_rest.'.$key, $value);
            }
            $backend = true;
            $loader->load('google_rest.xml');
            if (empty($factory)) {
                $factory = 'liip_search.google_rest.factory';
            }
        }
        if (!empty($config['clients']['google_cse']['enabled'])) {
            $container->setParameter($this->getAlias().'.controller.frontend_search_options', array(
                'search_template' => 'LiipSearchBundle:GoogleCse:search.html.twig',
                'query_param_name' => $config['query_param_name'],
                'template_options' => array(
                    'google_custom_search_id' => $config['clients']['google_cse']['cse_id'],
                ),
            ));

            $frontend = true;
            if (empty($factory)) {
                $controller = 'liip_search.controller.frontend_search:searchAction';
            }
        }

        unset($config['clients']);
        $container->setParameter('liip_search.controller.search_action', $controller);

        if ($frontend) {
            $loader->load('frontend_search.xml');
        }

        if (empty($factory)) {
            return false;
        }

        if ($backend) {
            $loader->load('backend_search.xml');
        }

        return $factory;
    }
}
