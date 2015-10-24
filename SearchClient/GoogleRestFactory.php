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
 * Adapter for google search REST API.
 *
 * If you configure more than one key for the search engines, the search engine
 * matching the requested locale will be used and the google API client is *not*
 * told to restrict to the locale. Otherwise, if restrictLanguage is set but
 * only one key is configured, the google API client is told to restrict the
 * locale in the request.
 */
class GoogleRestFactory implements SearchFactoryInterface
{
    /**
     * @var string
     */
    private $googleApiKey;

    /**
     * @var array
     */
    private $googleSearchKeys;

    /**
     * @var
     */
    private $googleSearchAPIUrl;

    /**
     * @var int Default limit for number of pages
     */
    private $maxPerPage;

    /**
     * @var bool
     */
    private $restrictLanguage;

    /**
     * @var string|bool
     */
    private $restrictToSite;

    /**
     * @param string      $apiKey           Key for Google Project
     * @param array       $searchKeys       Google search engine key or list indexed by locale
     * @param string      $apiUrl           REST API endpoint
     * @param int         $maxPerPage       Limit for results on a page.
     * @param string|bool $restrictLanguage Limit search results to requested language
     * @param string|bool $restrictToSite   If search results should be restricted to one site, specify the site
     */
    public function __construct($apiKey, $searchKeys, $apiUrl, $maxPerPage = 10, $restrictLanguage = false, $restrictToSite = false)
    {
        $this->googleApiKey = $apiKey;
        $this->googleSearchKeys = $searchKeys;
        $this->googleSearchAPIUrl = $apiUrl;
        $this->maxPerPage = $maxPerPage;
        $this->restrictLanguage = $restrictLanguage;
        $this->restrictToSite = $restrictToSite;
    }

    /**
     * Get search results from Google.
     *
     * {@inheritdoc}
     */
    public function getPagerfanta($query, $locale)
    {
        $adapter = new GoogleRestAdapter(
            $this->googleApiKey,
            $this->getSearchKey($locale),
            $this->googleSearchAPIUrl,
            $query,
            $this->restrictLanguage ? $locale : false,
            $this->restrictToSite
        );
        $pager = new Pagerfanta($adapter);

        $pager->setMaxPerPage($this->maxPerPage);

        return $pager;
    }

    /**
     * Get the search engine key that matches best for the requested locale.
     *
     * @param string $locale Preferred locale for the search engine.
     *
     * @return string The best search key for this locale
     */
    private function getSearchKey($locale)
    {
        if (isset($this->googleSearchKeys[$locale])) {
            return $this->googleSearchKeys[$locale];
        }

        return reset($this->googleSearchKeys);
    }
}
