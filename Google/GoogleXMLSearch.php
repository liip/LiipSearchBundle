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

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class GoogleXMLSearch
{

    protected $googleApiKey;

    protected $googleSearchKey;

    protected $restrictToSite;

    protected $restrictToLabels;

    /**
     * @param string $google_api_key Key for Google Project
     * @param string $google_search_key Key for cse search service
     * @param string $restrict_to_site If search results should be restricted to one site, specify the site
     * @param array $restrict_to_labels If search results should be restricted to one or more labels, specify the labels
     * @return \Liip\SearchBundle\Google\GoogleXMLSearch
     */
    public function __construct($google_api_key, $google_search_key, $restrict_to_site, $restrict_to_labels)
    {
        $this->googleApiKey = $google_api_key;
        $this->googleSearchKey = $google_search_key;
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
        $url = $this->getRequestUrl($this->googleApiKey, $this->googleSearchKey, $query, $lang, $start, $limit);
        $json = file_get_contents($url);

        if ($json === false || is_null($json)) {
            throw new \Exception('Error downloading Google Search Result');
        }

        $serializer = new Serializer(array(), array(new JsonEncoder()));
        $doc = $serializer->decode($json, 'json');


        if ($doc === null) {
            throw new \Exception('Error while decoding JSON data from Google Search Result');
        }

        return $this->extractSearchResults($doc);
    }

    /**
     * Builds request URL for google search XML API
     *
     * @param string $googleApiKey key for the Google project
     * @param string $googleSearchKey key for cse search service
     * @param string $query the search query (not encoded)
     * @param mixed $lang boolean false or language string (en, fr, de, etc.)
     * @param int $start item number to start with (first item is item 1)
     * @param int $limit how many results at most to return (valid values: 1 to 10)
     * @return array of search result information and items
     */
    protected function getRequestUrl($googleApiKey, $googleSearchKey, $query, $lang, $start, $limit)
    {
        $encodedQuery = $this->getGoogleEncodedString($query);

        $params = array(
            //'client' => 'google-csbe',
            'key' => $googleApiKey,
            'cx' => $googleSearchKey,
            //'output' => 'xml_no_dtd',
            //'ie' => 'UTF-8',            // input encoding
            //'oe' => 'UTF-8',            // output encoding
            'start' => $start,            // 1-based index of first item to return
            'num' => $limit,              // how many items (maximum) to return (from 1 to 10)
        );

        if ($lang !== false) {
            $params['lr'] = 'lang_' . $lang;    // results language
            $params['hl'] =  $lang;             // interface language, google recommends explicitly setting also for xml queries
        }

        if ($this->restrictToSite) {
            $params['as_sitesearch'] = $this->restrictToSite;
        } elseif (!empty($this->restrictToLabels)) {
            foreach ($this->restrictToLabels as $label) {
                $encodedQuery .= '+more&3' . $label;
            }
        }

        // The parameters don't have to be escaped (eg. ":" should remain as is)
        $queryString = '?' . urldecode(http_build_query($params)) . '&q=' . $encodedQuery;

        $url = 'https://www.googleapis.com/customsearch/v1' . $queryString;
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

        /*
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        //die();
        //*/

        // If the document is not an object, something went wrong here
        if (!is_array($data) || empty($data)) {
            return $results;
        }


        // Get count of estimated total available hits
        $results['information'] = $this->extractSearchInformation($data);
        $baseIndex = $results['information']['paging']['currentRequestItemRange']['start'];


        if (isset($data['items']) && count($data['items'])) {
            // Build the result set from the google response
            foreach($data['items'] as $index => $resultItem) {
                $results['items'][] = $this->extractSearchResultItem($resultItem, $index + $baseIndex);
            }
        }

        return $results;
    }

    /**
     * Get spelling suggestions from Google search response
     * @param \DOMXPath $xpath
     * @return array
     */
    protected function spellingSuggestions($xpath)
    {
        $spellingSuggestions = array();
        $suggestions = $xpath->query('/GSP/Spelling/Suggestion');
        if ($suggestions) {
            foreach ($suggestions as $suggestion) {
                if ($suggestion->hasAttributes()) {
                    if ($spellingSuggestion = $suggestion->attributes->getNamedItem('q')->value) {
                        $spellingSuggestions[] = $spellingSuggestion;
                    }
                }
            }
        }
        return $spellingSuggestions;
    }

    /**
     * Guess site based on url.
     * This could perhaps also be done by setting up some "refinements" in the cse and checking for those in the results.
     *
     * @param string $url Absolute url of item
     * @return string hostname
     */
    protected function extractSite($url)
    {
        $parts = explode('/', $url, 4);
        if (count($parts) < 3) {
            return null;
        }
        return $parts[2];
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
            'url' => $resultItemData['formattedUrl'],
            // @todo Implement the "MoreLikeThis" identification and extraction
            'moreLikeThis' => false,
            'site' => $this->extractSite('http://' . $resultItemData['formattedUrl']),
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
