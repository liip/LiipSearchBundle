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

use Liip\SearchBundle\Controller\SearchController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Pager
{
    private $urlGenerator;
    private $searchRoute;
    private $maxHeadItems;
    private $maxTailItems;
    private $maxAdjoiningItems;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param string                $searchRoute
     * @param integer               $maxHeadItems
     * @param integer               $maxTailItems
     * @param integer               $maxAdjoiningItems
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        $searchRoute,
        $maxHeadItems,
        $maxTailItems,
        $maxAdjoiningItems
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->searchRoute = $searchRoute;
        $this->maxHeadItems = $maxHeadItems;
        $this->maxTailItems = $maxTailItems;
        $this->maxAdjoiningItems = $maxAdjoiningItems;
    }

    /**
     * @param integer $estimated
     * @param integer $start
     * @param integer $perPage
     * @param string  $query
     *
     * @return array
     */
    public function paging($estimated, $start, $perPage, $query)
    {
        $pagingFirst = array();
        $pagingPrev = array();
        $pagingNext = array();
        $pagingLast = array();

        $dotsBefore = false;
        $dotsAfter = false;

        $currentPage = ($start - 1) / $perPage + 1;

        if ($estimated > $perPage) {
            // results start from 1, not 0.  Look for previous pages.
            $resultNum = $start - 1;
            if ($resultNum - $perPage * $this->maxAdjoiningItems > $this->maxHeadItems * $perPage) {
                // there is a gap (...) between "first" and "previous" pages
                $maxPrevious = $this->maxAdjoiningItems;
                $hasFirstPages = true;
            } else {
                // there is no gap
                $maxPrevious = $this->maxHeadItems + $this->maxAdjoiningItems;
                $hasFirstPages = false;
            }

            if ($hasFirstPages) {
                for ($i = 1; $i <= $this->maxHeadItems; $i++) {
                    $pagingFirst[$i] = $this->urlGenerator->generate(
                        $this->searchRoute,
                        array(
                            SearchController::QUERY_PARAMETER => $query,
                            SearchController::PAGE_PARAMETER => $i,
                        )
                    );
                }
            }

            for ($i = 0; $i < $maxPrevious  && $resultNum > 0; $i++) {
                $pageNum = $resultNum / $perPage;
                $pagingPrev[$pageNum] = $this->urlGenerator->generate(
                    $this->searchRoute,
                    array(
                        SearchController::QUERY_PARAMETER => $query,
                        SearchController::PAGE_PARAMETER => $pageNum,
                    )
                );
                $resultNum -= $perPage;
            }
            $pagingPrev = array_reverse($pagingPrev, true);

            // Look for subsequent pages
            $resultNum = $start - 1 + $perPage;
            if ($resultNum + $perPage * $this->maxAdjoiningItems < $estimated - $this->maxTailItems * $perPage) {
                // there is a gap (...) between "next" and "last" pages
                $maxNext = $this->maxAdjoiningItems;
                $hasLastPages = true;
            } else {
                // there is no gap
                $maxNext = $this->maxTailItems + $this->maxAdjoiningItems;
                $hasLastPages = false;
            }

            for ($i = 0; $i < $maxNext && $resultNum < $estimated; $i++) {
                $pageNum = $resultNum / $perPage + 1;
                $pagingNext[$pageNum] = $this->urlGenerator->generate(
                    $this->searchRoute,
                    array(
                        SearchController::QUERY_PARAMETER => $query,
                        SearchController::PAGE_PARAMETER => $pageNum,
                    )
                );
                $resultNum += $perPage;
            }

            $lastPage = (int) ($estimated / $perPage) + 1;

            if ($hasLastPages) {
                for ($i = $lastPage - $this->maxTailItems + 1; $i <= $lastPage; $i++) {
                    $pagingLast[$i] = $this->urlGenerator->generate(
                        $this->searchRoute, array(
                            SearchController::QUERY_PARAMETER => $query,
                            SearchController::PAGE_PARAMETER => $i,
                        )
                    );
                }
            }

            $dotsBefore =
                sizeof($pagingFirst) > 0
                || ($this->maxHeadItems === 0
                    && $currentPage-sizeof($pagingPrev) > 1
                )
            ;
            $dotsAfter =
                sizeof($pagingLast) > 0
                || ($this->maxTailItems === 0
                    && $currentPage+sizeof($pagingNext) < $lastPage
                )
            ;
        }

        return array(
            'first' => $pagingFirst,
            'prev' => $pagingPrev,
            'current' => $currentPage,
            'next' => $pagingNext,
            'last' => $pagingLast,
            'dotsBefore' => $dotsBefore,
            'dotsAfter' => $dotsAfter,
        );
    }
}
