<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Controller;

use Liip\SearchBundle\SearchFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Controller to handle search requests.
 */
class PagedSearchController
{
    /**
     * @var SearchFactoryInterface
     */
    private $searchFactory;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var array Hashmap with options to control the behaviour of this controller.
     */
    private $options;

    /**
     * Constructor.
     *
     * Supported options are:
     *     - query_param_name* Name of the URL GET parameter for the query.
     *     - page_param_name*  Name of the URL GET parameter for the page.
     *     - search_template   Template for the search page
     *     - template_options  Information to pass to the template as 'options'.
     *
     * @param EngineInterface        $templating
     * @param SearchFactoryInterface $searchFactory
     * @param array                  $options
     */
    public function __construct(
        SearchFactoryInterface $searchFactory,
        EngineInterface $templating,
        array $options
    ) {
        $this->searchFactory = $searchFactory;
        $this->templating = $templating;
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(array(
            'query_param_name',
            'page_param_name',
        ));
        $optionsResolver->setDefaults(array(
            'search_template' => 'LiipSearchBundle:Search:search.html.twig',
            'template_options' => array(),
        ));
        $this->options = $optionsResolver->resolve($options);
    }

    /**
     * Search method.
     *
     * @param Request $request Current request to fetch info from.
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $query = $request->get($this->options['query_param_name'], '');
        $page = $request->get($this->options['page_param_name'], 1);

        if (empty($query)) {
            return new Response($this->templating->render(
                $this->options['search_template'],
                array(
                    'query' => '',
                    'search_results' => array(),
                    'options' => $this->options['template_options'],
                    'estimated' => 0,
                )
            ));
        }

        $pager = $this->searchFactory->getPagerfanta($query, $request->getLocale());
        $pager->setCurrentPage($page);
        $results = $pager->getCurrentPageResults();

        return new Response($this->templating->render(
            $this->options['search_template'],
            array(
                'query' => $query,
                'pager' => $pager,
                'search_results' => $results,
                'estimated' => $pager->getNbResults(),
                'options' => $this->options['template_options'],
            )
        ));
    }
}
