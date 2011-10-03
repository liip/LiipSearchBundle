<?php

namespace Liip\SearchBundle\Pager;

class Pager
{
    protected $container;
    protected $router;
    protected $searchRoute;
    protected $maxExtremityItems;
    protected $maxAdjoiningItems;
    protected $templatingEngine;

    public function __construct($container, $router, $search_route, $templating_engine, $max_extremity_items, $max_adjoining_items)
    {
        $this->container = $container;
        $this->router = $router;
        $this->searchRoute = $search_route;
        $this->maxExtremityItems = $max_extremity_items;
        $this->maxAdjoiningItems = $max_adjoining_items;
        $this->templatingEngine = $templating_engine;
    }

    public function renderPaging($estimated, $start, $perPage, $query, $translationDomain)
    {
        $paging = $this->paging($estimated, $start, $perPage, $query);
        return $this->templatingEngine->render('LiipSearchBundle:Search:paging.html.twig',
            array(
                'paging' => $paging,
                'estimated' => $estimated,
                'translationDomain' => $translationDomain,
            )
        );
    }

    public function paging($estimated, $start, $perPage, $query)
    {
        $pagingFirst = array();
        $pagingPrev = array();
        $pagingNext = array();
        $pagingLast = array();

        $currentPage = ($start - 1) / $perPage + 1;

        $page_param_name = $this->container->getParameter('liip_search.page_param_name');
        $query_param_name = $this->container->getParameter('liip_search.query_param_name');

        if ($estimated > $perPage) {
            // results start from 1, not 0.  Look for previous pages.
            $resultNum = $start - 1;
            if ($resultNum - $perPage * $this->maxAdjoiningItems > $this->maxExtremityItems * $perPage) {
                // there is a gap (...) between "first" and "previous" pages
                $maxPrevious = $this->maxAdjoiningItems;
                $hasFirstPages = true;
            } else {
                // there is no gap
                $maxPrevious = $this->maxExtremityItems + $this->maxAdjoiningItems;
                $hasFirstPages = false;
            }

            if ($hasFirstPages) {
                for ($i = 1; $i <= $this->maxExtremityItems; $i++) {
                    $pagingFirst[$i] = $this->router->generate('search', array($query_param_name => $query, $page_param_name => $i));
                }
            }

            for ($i = 0; $i < $maxPrevious  && $resultNum > 0; $i++) {
                $pageNum = $resultNum / $perPage;
                $pagingPrev[$pageNum] = $this->router->generate('search', array($query_param_name => $query, $page_param_name => $pageNum));
                $resultNum -= $perPage;
            }
            $pagingPrev = array_reverse($pagingPrev, true);

            // Look for subsequent pages
            $resultNum = $start - 1 + $perPage;
            if ($resultNum + $perPage * $this->maxAdjoiningItems < $estimated - $this->maxExtremityItems * $perPage) {
                // there is a gap (...) between "next" and "last" pages
                $maxNext = $this->maxAdjoiningItems;
                $hasLastPages = true;
            } else {
                // there is no gap
                $maxNext = $this->maxExtremityItems + $this->maxAdjoiningItems;
                $hasLastPages = false;
            }

            for ($i = 0; $i < $maxNext && $resultNum < $estimated; $i++) {
                $pageNum = $resultNum / $perPage + 1;
                $pagingNext[$pageNum] = $this->router->generate('search', array($query_param_name => $query, $page_param_name => $pageNum));
                $resultNum += $perPage;
            }

            if ($hasLastPages) {
                $lastPage = (int)($estimated / $perPage) + 1;
                for ($i = $lastPage - $this->maxExtremityItems + 1; $i <= $lastPage; $i++) {
                    $pagingLast[$i] = $this->router->generate('search', array($query_param_name => $query, $page_param_name => $i));
                }

            }
        }

        return array(
            'first' => $pagingFirst,
            'prev' => $pagingPrev,
            'current' => $currentPage,
            'next' => $pagingNext,
            'last' => $pagingLast,
            );
    }
}

