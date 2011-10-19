<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle;

/**
 * Class for search
 */
interface SearchInterface
{
    /**
     * Search method
     * @param mixed $page string current result page to show or null
     * @param mixed $query string current search query or null
     * @param mixed $lang string language to use for restricting search results, or null
     * @param array $options any options which should be passed along to underlying search engine
     * @return string
     */
    public function search($page =  null, $query = null, $lang = null, $options = array());

    /**
     * Extract the page from the request (looks in GET, then POST).
     * If not present in the request or if less than 1, 1 will be returned.
     * @return int
     */
    public function requestedPage();

    /**
     * Extract the trimmed query from the request (looks in GET, then POST).
     * If not present in the request, an empty string will be returned.
     * @return string
     */
    public function requestedQuery();

    /**
     * Determine language used to restrict search results, if one should be used at all.
     * If results should not be restricted by language, this will return false.
     * @return mixed string(=locale) or bool(=false)
     */
    public function queryLanguage($lang = null);
}
