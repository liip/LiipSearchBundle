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

use Liip\SearchBundle\SearchInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Liip\SearchBundle\Pager\Pager;

/**
 * Controller to handle search requests and provide search input forms.
 */
class SearchController
{
    /**
     * URL parameter for the search string.
     */
    const QUERY_PARAMETER = 'query';

    /**
     * URL parameter for the paging information.
     */
    const PAGE_PARAMETER = 'page';

    /**
     * @var SearchInterface
     */
    private $searchClient;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var Pager
     */
    private $pager;

    /**
     * Constructor
     *
     * Supported options are:
     *     - search_route      Name of route used for submitting search query
     *     - results_per_page  Limit of results per page.
     *     - restrict_language Whether to search in all languages or only the request language.
     *     - search_template   Template for the search page
     *     - box_template      Template for displaying the search box
     *     - paging_template    Template for displaying the pager
     *     - template_options  Information to pass to the template as 'options'.
     *
     * @param SearchInterface       $searchClient
     * @param EngineInterface       $templating
     * @param UrlGeneratorInterface $urlGenerator
     * @param Pager                 $pager        Pager service
     * @param array                 $options
     */
    public function __construct(
        SearchInterface $searchClient,
        EngineInterface $templating,
        UrlGeneratorInterface $urlGenerator,
        Pager $pager,
        $options
    ) {
        $this->searchClient = $searchClient;
        $this->templating = $templating;
        $this->urlGenerator = $urlGenerator;
        $this->pager = $pager;
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults(array(
            'search_route' => 'liip_search',
            'results_per_page' => 10,
            'restrict_language' => false,
            'search_template' => 'LiipSearchBundle:Search:search.html.twig',
            'box_template' => 'LiipSearchBundle:Search:search_box.html.twig',
            'paging_template' => 'LiipSearchBundle:Search:paging.html.twig',
            'template_options' => array(),
        ));
        $this->options = $optionsResolver->resolve($options);
    }
    /**
     * Search method
     *
     * @param Request $request current request object, will be automatically injected by symfony when called as an action
     * @param mixed   $_locale    string language to use for restricting search results, or null
     *
     * @return Response
     */
    public function searchAction(Request $request, $_locale = null)
    {
        $query = $request->get(static::QUERY_PARAMETER, '');
        $page = $request->get(static::PAGE_PARAMETER, 1);
        if (empty($query)) {
            return new Response($this->templating->render(
                $this->options['search_template'],
                array(
                    'query' => $query,
                    'search_results' => array(),
                    'estimated' => 0,
                    'show_paging' => false,
                    'start' => 1,
                    'options' => $this->options['template_options'],
                )
            ));
        }

        $_locale = $this->determineQueryLanguage($_locale, $request);

        $searchResults = $this->searchClient->search(
            $query,
            ($page-1) * $this->options['results_per_page']+1,
            $this->options['results_per_page'],
            $_locale
        );

        if (!isset($searchResults['information']['paging'])) {
            $estimated = $start = 0;
            $showPaging = false;
        } else {
            $estimated = $searchResults['information']['paging']['estimatedTotalItemCount'];
            $start = $searchResults['information']['paging']['currentRequestItemRange']['start'];
            $showPaging = $estimated > $this->options['results_per_page'];
        }

        return new Response($this->templating->render(
            $this->options['search_template'],
            array(
                'query' => $query,
                'search_results' => $searchResults['items'],
                'estimated' => $estimated,
                'show_paging' => $showPaging,
                'start' => $start,
                'options' => $this->options['template_options'],
            )
        ));
    }

    /**
     * Determine language used to restrict search results, if one should be used at all.
     *
     * If restrictLanguage is false, this will return false.
     *
     * @param string  $lang    A known language to use if restrictLanguage is true.
     * @param Request $request
     *
     * @return string|boolean The language to use or false
     */
    private function determineQueryLanguage($lang = null, Request $request)
    {
        if (!$this->options['restrict_language']) {
            return false;
        }
        if (null !== $lang) {
            return $lang;
        }

        return $request->getLocale();
    }

    /**
     * Renders the search box
     *
     * @param Request $request
     *
     * @return Response
     */
    public function showSearchBoxAction(Request $request)
    {
        return new Response($this->templating->render(
            $this->options['box_template'],
            array(
                'search_url' => $this->urlGenerator->generate($this->options['search_route']),
                'field_id' => $request->get('field_id', 'query'),
                'css_class' => $request->get('css_class', 'search'),
                'query' => $request->query->get(static::QUERY_PARAMETER),
                'query_param_name' => static::QUERY_PARAMETER,
                'options' => $this->options['template_options'],
            )
        ));
    }

    /**
     * @param integer $estimated
     * @param integer $start
     * @param string  $query
     *
     * @return Response
     */
    public function showPagingAction($estimated, $start, $query)
    {
        $paging = $this->pager->paging($estimated, $start, $this->options['results_per_page'], $query);

        return new Response($this->templating->render(
            $this->options['paging_template'],
            array(
                'paging' => $paging,
                'estimated' => $estimated,
                'options' => $this->options['template_options'],
            )
        ));
    }
}
