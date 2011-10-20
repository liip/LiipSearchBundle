<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return \Liip\SearchBundle\Controller\DefaultController
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Renders the search box
     *
     * @param string $field_id
     * @param string $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSearchBoxAction($field_id, $query ='')
    {
        return $this->render('LiipSearchBundle:Search:search_box.html.twig', array(
                'searchRoute' =>  $this->get('router')->generate($this->container->getParameter('liip_search.search_route')),
                'translationDomain' =>  $this->container->getParameter('liip_search.translation_domain'),
                'field_id'  =>  $field_id,
                'query_param_name' => $this->container->getParameter('liip_search.query_param_name'),
                'searchTerm'    =>  $query,
            )
        );
    }

    /**
     * @param integer $estimated
     * @param integer $start
     * @param integer $perPage
     * @param string $query
     * @param string $translationDomain
     * @return void
     */
    public function showPagingAction($estimated, $start, $perPage, $query, $translationDomain)
    {
        $pager = $this->container->get('liip_search_pager');
        $paging = $pager->paging($estimated, $start, $perPage, $query);
        return $this->render('LiipSearchBundle:Search:paging.html.twig',
            array(
                'paging' => $paging,
                'estimated' => $estimated,
                'translationDomain' => $translationDomain,
            )
        );
    }
}
