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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contract for adapters to search services.
 */
interface SearchInterface
{
    /**
     * Query the ser
     *
     * @param string|null $query   string current search query or null
     * @param string|null $page    string current result page to show or null
     * @param string|null $lang    string language to use for restricting search results, or null
     * @param array       $options any options which should be passed along to underlying search engine
     * @param Request     $request current request object, will be automatically injected by symfony when called as an action
     *
     * @return Response
     */
    public function searchAction($query = null, $page = null, $lang = null, $options = array(), Request $request = null);

    /**
     * Determine locale to restrict search results.
     *
     * If results should not be restricted by language, this will return false.
     *
     * @param string  $lang
     * @param Request $request
     *
     * @return string|boolean The locale to use or false
     */
    public function queryLanguage($lang = null, Request $request);
}
