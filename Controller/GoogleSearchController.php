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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Liip\SearchBundle\SearchInterface;
use Liip\SearchBundle\Google\GoogleXMLSearch;
use Liip\SearchBundle\Helper\SearchParams;

/**
 * Class for search with google's XML API interface.
 * Requires a configured "Google site search" to be able to make queries.
 */
class GoogleSearchController extends Controller implements SearchInterface
{

    protected $container;
    protected $google;
    protected $perPage;
    protected $restrictByLanguage;
    protected $translationDomain;
    protected $pageParameterKey;
    protected $queryParameterKey;
    protected $searchRoute;
    protected $request;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Liip\SearchBundle\Google\GoogleXMLSearch $google_search
     * @param integer $results_per_page
     * @param boolean $restrict_by_language
     * @param string $translation_domain
     * @param string $page_parameter_key parameter name used for page
     * @param string $query_parameter_key parameter name used for search term
     * @param string $search_route route used for submitting search query
     */
    public function __construct(ContainerInterface $container, GoogleXMLSearch $google_search, $results_per_page, $restrict_by_language,
        $translation_domain, $page_parameter_key, $query_parameter_key, $search_route)
    {
        $this->container = $container;
        $this->google = $google_search;
        $this->perPage = $results_per_page;
        $this->restrictByLanguage = $restrict_by_language;
        $this->translationDomain = $translation_domain;
        $this->pageParameterKey = $page_parameter_key;
        $this->queryParameterKey = $query_parameter_key;
        $this->searchRoute = $search_route;
        $this->request = $this->getRequest();
    }

    /**
     * Search method
     * @param mixed $page string current result page to show or null
     * @param mixed $query string current search query or null
     * @param mixed $lang string language to use for restricting search results, or null
     * @param array $options any options which should be passed along to underlying search engine
     * @return string
     */
    public function search($page =  null, $query = null, $lang = null, $options = array())
    {

        if (null === $page) {
            // If the page param is not given, it's value is read in the request
            $page = SearchParams::requestedPage($this->request, $this->pageParameterKey);
        }

        if (null === $query) {
            // If the query param is not given, it's value is read in the request
            $query = SearchParams::requestedQuery($this->request, $this->queryParameterKey);
        }

        $lang = $this->queryLanguage($lang);

        try {
            $searchResults = $this->google->getSearchResults($query, $lang, ($page-1) * $this->perPage, $this->perPage);
        } catch(\Exception $e) {
            return $this->render('LiipSearchBundle:Search:failure.html.twig', array('searchTerm' => $query));
        }

        if (!isset($searchResults['information']['paging'])) {
            $estimated = $start = 0;
            $showPaging = false;
        } else {
            $estimated = $searchResults['information']['paging']['estimatedTotalItemCount'];
            $start = $searchResults['information']['paging']['currentRequestItemRange']['start'];
            $showPaging = $estimated > $this->perPage;
        }

        return $this->render('LiipSearchBundle:Search:search.html.twig',
                array(
                    'searchTerm' => $query,
                    'searchResults' => $searchResults['items'],
                    'estimated' => $estimated,
                    'translationDomain' => $this->translationDomain,
                    'showPaging' => $showPaging,
                    'start' => $start,
                    'perPage' => $this->perPage,
                    'searchRoute' => $this->searchRoute,
                ));
    }

    /**
     * Determine language used to restrict search results, if one should be used at all.
     * If $this->restrictByLanguage is false, this will return false.
     * @return mixed string(=locale) or bool(=false)
     */
    public function queryLanguage($lang = null)
    {
        if (!$this->restrictByLanguage) {
            return false;
        }
        if (null !== $lang) {
            return $lang;
        }
        return $this->request->getSession()->getLocale();
    }
}
