<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Search;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Liip\SearchBundle\Helper\GoogleXMLSearch;

/**
 * class for search
 */
class GoogleSearch
{

    protected $container;
    protected $searchPager;
    protected $google;
    protected $perPage;
    protected $restrictByLanguage;
    protected $translationDomain;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Liip\SearchBundle\Helper\GoogleXMLSearch $google_search
     * @param \Liip\SearchBundle\Pager\Pager $search_pager
     * @param integer $results_per_page
     * @param boolean $restrict_by_language
     * @param string $translation_domain
     */
    public function __construct($container, GoogleXMLSearch $google_search, $search_pager, $results_per_page, $restrict_by_language, $translation_domain)
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
     * @param mixed $page The current result page to show or null
     * @param mixed $query The current search query or null
     * @return string
     */
    public function search($page =  null, $query = null)
    {
        $request = $this->container->get('request');

        if (!$page) {
            // If the page param is not given, it's value is read in the request
            $key = $this->container->getParameter('liip_search.page_param_name');
            $page = intval($request->query->get($key));
        }

        if (!$query) {
            // If the query param is not given, it's value is read in the request
            $key = $this->container->getParameter('liip_search.query_param_name');
            $query = $request->query->get($key);
        }

        $templateEngine = $this->container->get('templating');

        if ($page < 1) {
            $page = 1;
        }

        if ($this->restrictByLanguage) {
            $lang = $this->container->get('session')->getLocale();
        } else {
            $lang = false;
        }
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

}
