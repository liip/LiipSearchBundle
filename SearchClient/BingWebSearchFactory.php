<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\SearchClient;

use Liip\SearchBundle\SearchFactoryInterface;
use Pagerfanta\Pagerfanta;

/**
 * Adapter for bing web search API.
 */
class BingWebSearchFactory implements SearchFactoryInterface
{
    /**
     * Bing Web Search API Key
     * 
     * @var string
     */
    private $apiKey;

    /**
     * Bing Web Search API Base URL
     * 
     * @var string
     */
    private $apiUrl;

    /**
     * Maximum number of results to show per page
     * 
     * @var string
     */
    private $maxPerPage;

    /**
     * Which domains should the results be restricted to
     * 
     * @var string[]|array
     */
    private $restrictToSites = [];

    /**
     * @param string $apiKey 
     * @param string $apiUrl 
     * @param int $maxPerPage 
     * @param string[]|array $restrictToSites 
     */
    public function __construct($apiKey, $apiUrl, $maxPerPage, array $restrictToSites = [])
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->maxPerPage = $maxPerPage;
        $this->restrictToSites = $restrictToSites;
    }

    /**
     * {@inheritdoc}
     */
    public function getPagerfanta($query, $lang)
    {
        $adapter = new BingWebSearchAdapter(
            $this->apiKey,
            $this->apiUrl,
            $query,
            $this->restrictToSites
        );
        $pager = new Pagerfanta($adapter);

        $pager->setMaxPerPage($this->maxPerPage);

        return $pager;
    }

}
