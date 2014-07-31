<?php

/*
 * This file is part of the Liip/SearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Google;

use Liip\SearchBundle\Exception\GoogleSearchException;

class GoogleXMLSearch
{

    protected $googleApiKey;

    protected $googleSearchAPIUrl;

    protected $googleSearchKey;

    protected $restrictToSite;

    protected $restrictToLabels;

    /**
     * @param string $google_api_key Key for Google Project
     * @param string $google_search_key Key for cse search service
     * @param $google_search_api_url
     * @param string $restrict_to_site If search results should be restricted to one site, specify the site
     * @param array $restrict_to_labels If search results should be restricted to one or more labels, specify the labels
     * @return \Liip\SearchBundle\Google\GoogleXMLSearch
     */
    public function __construct($google_api_key, $google_search_key, $google_search_api_url, $restrict_to_site, $restrict_to_labels)
    {
        $this->googleApiKey = $google_api_key;
        $this->googleSearchKey = $google_search_key;
        $this->googleSearchAPIUrl = $google_search_api_url;
        $this->restrictToSite = $restrict_to_site;
        $this->restrictToLabels = $restrict_to_labels;
    }

    /**
     * Get search results from Google.
     *
     * Returns an array of the form:
     *   items => array(
     *      array(
     *        'title' => 'Liip AG // Firma // Jobs // Stellenangebot: JavaScript Developer 60 <b>...</b>'
     *        'summary' => 'Liip AG - <b>Agile</b> Web Development. Zürich, Fribourg, Lausanne, Bern. Mobile <br>  Mode &middot; news &middot; Referenzen &middot; Dienstleistungen &middot; Technologie &middot; Firma &middot; Team &middot; Jobs <b>...</b>'
     *        'url' => 'http://www.liip.ch/company/jobs/javascript-developer.html'
     *        'moreLikeThis' => true   // if google can find "more pages like this"
     *        'site' => 'www.liip.ch'  // hostname
     *        'index' => '21'          // this item's index in the total number of search results: here for example, the search was done with $startItem = 20
     *        'mimetype' => '??'       // may or may not be present. according to google documentation, this will have the MIME type of the result; I haven't seen it.  (perhaps only for doc, pdf, etc.?)
     *      ),
     *      array(
     *          //...
     *      ),
     *      // etc.
     *   ),
     *   information => array(
     *      'spellingSuggestions' => array(
     *          'suggestedSpelling1',
     *          'suggestedSpelling2',
     *          // etc.
     *      ),
     *     'paging' => array(
     *       'estimatedTotalItemCount' => 93,
     *       'currentRequestItemRange' => array(
     *         'start' => 21   // note: first item is 1 if search was done with startItem = 0
     *         'end' => 30
     *        )
     *     )
     *   )
     *
     * Either or both of 'items' and 'information' may be empty, if there were no matched results,
     * or if there was an error processing the response.
     * There are not always spellingSuggestions provided, but there may be, especially if no items were found.
     *
     * @param string $query the search query (not url encoded)
     * @param mixed $lang boolean false or language string (en, fr, de, etc.)
     * @param int $start item number to start with (first item is item 1)
     * @param int $limit how many results at most to return
     * @throws \Exception
     * @return array of search result information and items
     */
    public function getSearchResults($query, $lang, $start, $limit)
    {
        if (empty($query)) {
            return array(
                'items' => array(),
                'information' => array(),
            );
        }

        $url = $this->getRequestUrl($query, $lang, $start, $limit);
        try {
            $json = @file_get_contents($url);
        } catch (\Exception $e) {
            // @todo: provide a more clear error message, extract it from Google HTTP error message?
            throw new GoogleSearchException('Error while getting the Google Search Engine API data', 0, $e);
        }

        if ($json === false || is_null($json)) {
            throw new GoogleSearchException('Empty response received from Google Search Engine API');
        }

        // Decoding JSON data as associative Array
        $doc = json_decode($json, true);

        if ($doc === null) {
            throw new GoogleSearchException('Error while decoding JSON data from Google Search API');
        }

        return $this->extractSearchResults($doc);
    }

    /**
     * Builds request URL for google search XML API
     *
     * @param string $query the search query (not encoded)
     * @param mixed $lang boolean false or language string (en, fr, de, etc.)
     * @param int $start item number to start with (first item is item 1)
     * @param int $limit how many results at most to return (valid values: 1 to 10)
     * @return array of search result information and items
     * @see https://developers.google.com/custom-search/json-api/v1/using_rest
     */
    public function getRequestUrl($query, $lang, $start, $limit)
    {
        $encodedQuery = $this->getGoogleEncodedString($query);

        $params = array(
            'key' => $this->googleApiKey,      // API key (REQUIRED)
            'cx' => $this->googleSearchKey,    // Custom search engine ID (REQUIRED)
            // 'alt' => 'json',          // Data format for the response. Values: json|atom Default: json
            // 'fields' => null,         // Selector specifying a subset of fields to include in the response.
            // 'prettyPrint' => true,      // Returns response with indentations and line breaks. Default: true
            'start' => $start,            // The index of the first result to return (1-based index).
            'num' => $limit,              // Number of search results to return. Valid values: 1 to 10.
        );

        if ($lang !== false) {
            $params['lr'] = 'lang_' . $lang;    // Restricts the search to documents written in a particular language
            $params['hl'] =  $lang;             // Sets the user interface language. Google recommends explicitly
                                                //   setting also for xml queries
        }

        if ($this->restrictToSite) {
            // Specifies all search results should be pages from a given site.
            $params['siteSearch'] = $this->restrictToSite;
        }
        //elseif (!empty($this->restrictToLabels)) {
        //    foreach ($this->restrictToLabels as $label) {
        //        $encodedQuery .= '+more&3' . $label;
        //    }
        //}

        // The parameters don't have to be escaped (eg. ":" should remain as is)
        $queryString = '?' . urldecode(http_build_query($params)) . '&q=' . $encodedQuery;

        $url = $this->googleSearchAPIUrl . $queryString;
        return $url;
    }

    /**
     * Encode a string to be passed to the google search service
     * See http://www.google.com/cse/docs/resultsxml.html#urlEscaping
     *
     * @param string string raw, non-encoded string
     * @return string encoded string
     */
    protected function getGoogleEncodedString($string)
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
     * @param array $data
     * @return array
     */
    protected function extractSearchResults($data)
    {
        $results = array(
            'items' => array(),
            'information' => array(),
        );

        // If the document is not an array, ot it is empty something went wrong here or the query was empty.
        if (!is_array($data) || empty($data)) {
            return $results;
        }

        // Get count of estimated total available hits.
        $results['information'] = $this->extractSearchInformation($data);
        $baseIndex = $results['information']['paging']['currentRequestItemRange']['start'];


        if (isset($data['items']) && count($data['items'])) {
            // Build the result set from the google response.
            foreach($data['items'] as $index => $resultItem) {
                $results['items'][] = $this->extractSearchResultItem($resultItem, $index + $baseIndex);
            }
        }

        return $results;
    }

    /**
     * Extract the search results from the Google search response
     * @param $resultItemData
     * @param $index
     * @return array
     */
    protected function extractSearchResultItem($resultItemData, $index)
    {
        $result = array(
            'title' => $resultItemData['htmlTitle'],
            'plainTitle' => $resultItemData['title'],
            'summary' => $resultItemData['htmlSnippet'],
            'plainSummary' => $resultItemData['snippet'],
            'url' => $resultItemData['link'],
            // @todo Implement the "MoreLikeThis" identification and extraction
            'moreLikeThis' => false,
            'site' => parse_url($resultItemData['link'], PHP_URL_HOST),
            'index' => $index,
            'thumbnail' => false,
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
     * @param $data
     * @return array
     */
    protected function extractSearchInformation($data)
    {
        $request = current($data['queries']['request']);
        return array(
            'searchTime' => $data['searchInformation']['searchTime'],
            'paging' => array(
                'estimatedTotalItemCount' => $data['searchInformation']['totalResults'],
                'currentRequestItemRange' => array(
                    'start' => $request['startIndex'],
                    'end' => $request['startIndex'] + $request['count'] -1,
                )
            )
        );
    }
}
