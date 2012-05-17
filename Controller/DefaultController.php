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

use Symfony\Component\HttpFoundation\Response,
    Symfony\Bundle\FrameworkBundle\Templating\EngineInterface,
    Symfony\Component\Routing\RouterInterface;

class DefaultController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $templating;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    protected $pager;
    protected $translationDomain;
    protected $queryParameterKey;
    protected $searchRoute;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param $pager search pager service
     * @param string $translation_domain
     * @param string $query_parameter_key parameter name used for search term
     * @param string $search_route route used for submitting search query
     * @return \Liip\SearchBundle\Controller\DefaultController
     */
    public function __construct(EngineInterface $templating, RouterInterface $router, $pager, $translation_domain, $query_parameter_key, $search_route)
    {
        $this->templating = $templating;
        $this->router = $router;
        $this->pager = $pager;
        $this->translationDomain = $translation_domain;
        $this->queryParameterKey = $query_parameter_key;
        $this->searchRoute = $search_route;
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
        return new Response($this->templating->render('LiipSearchBundle:Search:search_box.html.twig', array(
                'searchRoute' =>  $this->router->generate($this->searchRoute),
                'translationDomain' =>  $this->translationDomain,
                'field_id'  =>  $field_id,
                'query_param_name' => $this->queryParameterKey,
                'searchTerm'    =>  $query,
            )));
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
        $paging = $this->pager->paging($estimated, $start, $perPage, $query);
        return new Response($this->templating->render('LiipSearchBundle:Search:paging.html.twig',
            array(
                'paging' => $paging,
                'estimated' => $estimated,
                'translationDomain' => $translationDomain,
            )));
    }
}
