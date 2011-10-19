<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Google;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Liip\SearchBundle\SearchInterface;
use Liip\SearchBundle\Google\GoogleXMLSearch;

/**
 * Class for search with google's XML API interface.
 * Requires a configured "Google site search" to be able to make queries.
 */
class GoogleSearch implements SearchInterface
{

    protected $container;
    protected $searchPager;
    protected $google;
    protected $perPage;
    protected $restrictByLanguage;
    protected $translationDomain;
    protected $request;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Liip\SearchBundle\Google\GoogleXMLSearch $google_search
     * @param \Liip\SearchBundle\Pager\Pager $search_pager
     * @param integer $results_per_page
     * @param boolean $restrict_by_language
     * @param string $translation_domain
     */
    public function __construct(ContainerInterface $container, GoogleXMLSearch $google_search, $search_pager,
        $results_per_page, $restrict_by_language, $translation_domain)
    {
        $this->container = $container;
        $this->google = $google_search;
        $this->searchPager = $search_pager;
        $this->perPage = $results_per_page;
        $this->restrictByLanguage = $restrict_by_language;
        $this->translationDomain = $translation_domain;
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

        $templateEngine = $this->container->get('templating');

        if (null === $page) {
            // If the page param is not given, it's value is read in the request
            $page = $this->requestedPage();
        }

        if (null === $query) {
            // If the query param is not given, it's value is read in the request
            $query = $this->requestedQuery();
        }

        $lang = $this->queryLanguage($lang);

        try {
            $searchResults = $this->google->getSearchResults($query, $lang, ($page-1) * $this->perPage, $this->perPage);
        } catch(\Exception $e) {
            return $templateEngine->render('LiipSearchBundle:Search:failure.html.twig', array('searchTerm' => $query));
        }

        if (!isset($searchResults['information']['paging'])) {
            $estimated = $start = 0;
            $showPaging = false;
        } else {
            $estimated = $searchResults['information']['paging']['estimatedTotalItemCount'];
            $start = $searchResults['information']['paging']['currentRequestItemRange']['start'];
            $showPaging = $estimated > $this->perPage;
        }

        if ($showPaging) {
            $pagingHtml = $this->searchPager->renderPaging($estimated, $start, $this->perPage, $query, $this->translationDomain);
        } else {
            $pagingHtml = '';
        }

        return $templateEngine->render('LiipSearchBundle:Search:search.html.twig',
                array(
                    'searchTerm' => $query,
                    'searchResults' => $searchResults['items'],
                    'estimated' => $estimated,
                    'pagingHtml' => $pagingHtml,
                    'translationDomain' => $this->translationDomain,
                    'search_route' => $this->container->getParameter('liip_search.search_route'),
                ));
    }

    /**
     * Extract the page from the request (looks in GET, then POST).
     * If not present in the request or if less than 1, 1 will be returned.
     * @return int
     */
    public function requestedPage()
    {
        $request = $this->getRequest();
        $key = $this->container->getParameter('liip_search.page_param_name');
        $page = $request->query->get($key);
        if (null === $page) {
            $page = $request->request->get($key);
            if (null === $page) {
                return 1;
            }
        }
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }
        return $page;
    }

    /**
     * Extract the trimmed query from the request (looks in GET, then POST).
     * If not present in the request, an empty string will be returned.
     * @return string
     */
    public function requestedQuery()
    {
        $request = $this->getRequest();
        $key = $this->container->getParameter('liip_search.query_param_name');
        $query = $request->query->get($key);
        if (null === $query) {
            $query = $request->request->get($key);
            if (null === $query) {
                return '';
            }
        }
        return trim($query);
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
        return $this->getRequest()->getSession()->getLocale();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        if (null === $this->request) {
            $this->request = $this->container->get('request');
        }
        return $this->request;
    }
}
