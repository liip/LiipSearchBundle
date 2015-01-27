<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\SearchClient;

use Liip\SearchBundle\Exception\SearchException;
use Liip\SearchBundle\SearchInterface;

/**
 * Adapter for google search REST API.
 */
class GoogleRestClient implements SearchInterface
{
    /**
     * @var string
     */
    private $googleApiKey;

    /**
     * @var string
     */
    private $googleSearchKey;

    /**
     * @var
     */
    private $googleSearchAPIUrl;

    /**
     * @var string|boolean
     */
    private $restrictToSite;

    /**
     * @param string         $apiKey         Key for Google Project
     * @param string         $searchKey      Key for cse search service
     * @param string         $apiUrl         REST API endpoint
     * @param string|boolean $restrictToSite If search results should be restricted to one site, specify the site
     */
    public function __construct($apiKey, $searchKey, $apiUrl, $restrictToSite = false)
    {
        $this->googleApiKey = $apiKey;
        $this->googleSearchKey = $searchKey;
        $this->googleSearchAPIUrl = $apiUrl;
        $this->restrictToSite = $restrictToSite;
    }

    /**
     * Get search results from Google.
     *
     * {@inheritDoc}
     */
    public function search($query, $offset = null, $limit = null, $lang = false, $options = array())
    {
        $url = $this->buildRequestUrl($query, $lang, $offset, $limit);
        try {
            $json = @file_get_contents($url);
        } catch (\Exception $e) {
            // @todo: provide a more clear error message, extract it from Google HTTP error message?
            throw new SearchException('Error while getting the Google Search Engine API data', 0, $e);
        }

        if ($json === false || is_null($json)) {
            throw new SearchException('Empty response received from Google Search Engine API');
        }

        // Decoding JSON data as associative Array
        $doc = json_decode($json, true);

        if ($doc === null) {
            throw new SearchException('Error while decoding JSON data from Google Search API: '.json_last_error_msg());
        }

        return $this->extractSearchResults($doc);
    }

    /**
     * Builds request URL for google search REST API
     *
     * @param string         $query the search query (not encoded)
     * @param string|boolean $lang  boolean false or language string (en, fr, de, etc.)
     * @param int            $start item number to start with (first item is item 1)
     * @param int            $limit how many results at most to return (valid values: 1 to 10)
     *
     * @return array of search result information and items
     *
     * @see https://developers.google.com/custom-search/json-api/v1/using_rest
     */
    private function buildRequestUrl($query, $lang, $start, $limit)
    {
        $encodedQuery = $this->getGoogleEncodedString($query);

        $params = array(
            'key' => $this->googleApiKey,
            'cx' => $this->googleSearchKey,
            'start' => $start,              // The index of the first result to return (1-based index).
            'num' => $limit,       // Number of search results to return. Valid values: 1 to 10.
        );

        if ($lang !== false) {
            $params['lr'] = 'lang_'.$lang;    // Restricts the search to documents written in a particular language
            $params['hl'] =  $lang;             // Sets the user interface language. Google recommends explicitly
                                                //   setting also for xml queries
        }

        if ($this->restrictToSite) {
            // Specifies all search results should be pages from a given site.
            $params['siteSearch'] = $this->restrictToSite;
        }

        // The parameters don't have to be escaped (eg. ":" should remain as is)
        $queryString = '?'.urldecode(http_build_query($params)).'&q='.$encodedQuery;

        $url = $this->googleSearchAPIUrl.$queryString;

        return $url;
    }

    /**
     * Encode a string to be passed to the google search service
     * See http://www.google.com/cse/docs/resultsxml.html#urlEscaping
     *
     * @param string string raw, non-encoded string
     *
     * @return string encoded string
     */
    private function getGoogleEncodedString($string)
    {
        $encoded = rawurlencode($string);
        $encoded = str_replace(
            array('-',   '_',   '.'),
            array('%2D', '%5F', '%2E'),
            $encoded
        );
        $encoded = preg_replace('/(%20)+/', '+', $encoded);

        return $encoded;
    }

    /**
     * Extract the search results from the Google search response
     *
     * @param array $data Raw google REST API result.
     *
     * @return array
     */
    private function extractSearchResults(array $data)
    {
        $results = array(
            'items' => array(),
            'information' => array(),
        );

        // If the document is not an array, ot it is empty something went wrong here or the query was empty.
        if (!is_array($data) || empty($data)) {
            throw new SearchException('Unexpected empty result from google search API');
        }

        // Get count of estimated total available hits.
        $results['information'] = $this->extractSearchInformation($data);
        $baseIndex = $results['information']['paging']['currentRequestItemRange']['start'];

        if (isset($data['items']) && count($data['items'])) {
            // Build the result set from the google response.
            foreach ($data['items'] as $index => $resultItem) {
                $results['items'][] = $this->extractSearchResultItem($resultItem, $index + $baseIndex);
            }
        }

        return $results;
    }

    /**
     * Extract a search result item from the google REST API.
     *
     * @param array $resultItemData Information about this item.
     * @param int   $index          Index of this item.
     *
     * @return array
     */
    private function extractSearchResultItem($resultItemData, $index)
    {
        $result = array(
            'htmlTitle' => $resultItemData['htmlTitle'],
            'title' => $resultItemData['title'],
            'htmlSnippet' => $resultItemData['htmlSnippet'],
            'snippet' => $resultItemData['snippet'],
            'url' => $resultItemData['link'],
            'site' => parse_url($resultItemData['link'], PHP_URL_HOST),
            'htmlUrl' => $resultItemData['formattedUrl'],
            'index' => $index,
        );

        // Adding extra content: page preview (if available)
        if (isset($resultItemData['pagemap']['cse_thumbnail']) && !empty($resultItemData['pagemap']['cse_thumbnail'])) {
            $thumbnail = current($resultItemData['pagemap']['cse_thumbnail']);
            $result['thumbnail'] = $thumbnail;
        }

        return $result;
    }

    /**
     * Gets paging information
     *
     * @param array $data Raw google REST API result.
     *
     * @return array
     */
    private function extractSearchInformation(array $data)
    {
        $request = current($data['queries']['request']);

        return array(
            'searchTime' => $data['searchInformation']['searchTime'],
            'paging' => array(
                'estimatedTotalItemCount' => $data['searchInformation']['totalResults'],
                'currentRequestItemRange' => array(
                    'start' => $request['startIndex'],
                    'end' => $request['startIndex'] + $request['count'] -1,
                ),
            ),
        );
    }
}
