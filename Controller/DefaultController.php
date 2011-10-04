<?php

namespace Liip\SearchBundle\Controller;

use Symfony\Component\DependencyInjection\Container,
    Symfony\Component\Routing\Router,
    Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    protected $router;

    public function __construct($container, $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    /**
     * Renders the search box
     *
     * @param string $field_id
     * @param string $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSearchBoxAction($field_id, $query)
    {
        $engine = $this->container->get('templating');
        return new Response(
            $engine->render('LiipSearchBundle:Search:search_box.html.twig', array(
                'search_route' =>  $this->router->generate('search'),
                'translationDomain' =>  $this->container->getParameter('liip_search.translation_domain'),
                'field_id'  =>  $field_id,
                'query_param_name' => $this->container->getParameter('liip_search.query_param_name'),
                'searchTerm'    =>  $query,
            ))
        );
    }
}
