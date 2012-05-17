<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class SearchParams
{

    /**
     * Extract the page from the request (looks in GET, then POST).
     * If not present in the request or if less than 1, 1 will be returned.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $pageParameterKey
     * @return int
     */
    static public function requestedPage(Request $request, $pageParameterKey)
    {
        $page = $request->query->get($pageParameterKey);
        if (null === $page) {
            $page = $request->request->get($pageParameterKey);
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $queryParameterKey
     * @return string
     */
    static public function requestedQuery(Request $request, $queryParameterKey)
    {
        $query = $request->query->get($queryParameterKey);
        if (null === $query) {
            $query = $request->request->get($queryParameterKey);
            if (null === $query) {
                return '';
            }
        }

        return trim($query);
    }
}
