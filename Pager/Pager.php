<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Pager;

class Pager
{
    protected $container;
    protected $router;
    protected $searchRoute;
    protected $maxExtremityItems;
    protected $maxAdjoiningItems;
    protected $queryParameterKey;
    protected $pageParameterKey;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Component\Routing\Router $router
     * @param string $search_route
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating_engine
     * @param integer $max_extremity_items
     * @param integer $max_adjoining_items
     * @param string $page_parameter_key
     * @param string $query_parameter_key
     * @return \Liip\SearchBundle\Pager\Pager
     */
    public function __construct($container, $router, $search_route, $max_extremity_items, $max_adjoining_items,
        $query_parameter_key, $page_parameter_key)
    {
        $this->container = $container;
        $this->router = $router;
        $this->searchRoute = $search_route;
        $this->maxExtremityItems = $max_extremity_items;
        $this->maxAdjoiningItems = $max_adjoining_items;
        $this->queryParameterKey = $query_parameter_key;
        $this->pageParameterKey = $page_parameter_key;
    }

    /**
     * @param integer $estimated
     * @param integer $start
     * @param integer $perPage
     * @param string $query
     * @return array
     */
    public function paging($estimated, $start, $perPage, $query)
    {
        $pagingFirst = array();
        $pagingPrev = array();
        $pagingNext = array();
        $pagingLast = array();

        $currentPage = ($start - 1) / $perPage + 1;

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
                    $pagingFirst[$i] = $this->router->generate($this->searchRoute, array($this->queryParameterKey => $query, $this->pageParameterKey => $i));
                }
            }

            for ($i = 0; $i < $maxPrevious  && $resultNum > 0; $i++) {
                $pageNum = $resultNum / $perPage;
                $pagingPrev[$pageNum] = $this->router->generate($this->searchRoute, array($this->queryParameterKey => $query, $this->pageParameterKey => $pageNum));
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
                $pagingNext[$pageNum] = $this->router->generate($this->searchRoute, array($this->queryParameterKey => $query, $this->pageParameterKey => $pageNum));
                $resultNum += $perPage;
            }

            if ($hasLastPages) {
                $lastPage = (int)($estimated / $perPage) + 1;
                for ($i = $lastPage - $this->maxExtremityItems + 1; $i <= $lastPage; $i++) {
                    $pagingLast[$i] = $this->router->generate($this->searchRoute, array($this->queryParameterKey => $query, $this->pageParameterKey => $i));
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

