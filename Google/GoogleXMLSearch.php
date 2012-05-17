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

class GoogleXMLSearch
{

    protected $googleSearchKey;

    protected $restrictToSite;

    protected $restrictToLabels;

    /**
     * @param string $googleSearchKey key for cse search service
     * @param string $restrict_to_site If search results should be restricted to one site, specify the site
     * @param array  $restrict_to_labels If search results should be restricted to one or more labels, specify the labels
     * @return \Liip\SearchBundle\Google\GoogleXMLSearch
     */
    public function __construct($google_search_key, $restrict_to_site, $restrict_to_labels)
    {
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
     * @param int $start item number to start with (first item is item 0)
     * @param int $limit how many results at most to return
     * @result array of search result information and items
     */
    public function getSearchResults($query, $lang, $start, $limit)
    {
        $url = $this->getRequestUrl($this->googleSearchKey, $query, $lang, $start, $limit);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        if (!$doc->load($url)) {
            // todo: log an error message or throw an exception
            return array();
        }
        return $this->extractSearchResults($doc);
    }

    /**
     * Builds request URL for google search XML API
     *
     * @param string $googleSearchKey key for cse search service
     * @param string $query the search query (not encoded)
     * @param mixed $lang boolean false or language string (en, fr, de, etc.)
     * @param int $start item number to start with (first item is item 0)
     * @param int $limit how many results at most to return
     * @result array of search result information and items
     */
    protected function getRequestUrl($googleSearchKey, $query, $lang, $start, $limit)
    {
        $encodedQuery = $this->getGoogleEncodedString($query);

        $params = array(
            'client' => 'google-csbe',
            'cx' => $googleSearchKey,
            'output' => 'xml_no_dtd',
            'ie' => 'UTF-8',            // input encoding
            'oe' => 'UTF-8',            // output encoding
            'start' => $start,          // how many items to skip before collecting items
            'num' => $limit,            // how many items (maximum) to return
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

        $queryString = '?' . http_build_query($params) . '&q=' . $encodedQuery;

        $url = 'http://www.google.com/cse' . $queryString;
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
     * @param DOMDocument $doc
     * @return array
     */
    protected function extractSearchResults($doc)
    {
        $results = array(
            'items' => array(),
            'information' => array(),
        );

        $xpath = new \DOMXPath($doc);

        // get any spelling suggestions
        $spellingSuggestions = $this->spellingSuggestions($xpath);
        if (count($spellingSuggestions)) {
            $results['information']['spellingSuggestions'] = $spellingSuggestions;
        }

        // Get count of estimated total available hits, return now if there are none (which means no items were found)
        if (!$pagingInformation = $this->pagingInformation($xpath)) {
            return $results;
        }
        $results['information']['paging'] = $pagingInformation;

        $results['items'] = $this->searchResultItems($xpath);

        return $results;
    }

    /**
     * Get spelling suggestions from Google search response
     * @param DomXPath $xpath
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
     * Gets paging information
     *
     * @param DomXPath $xpath
     * @return mixed null if no information available or array
     */
    protected function pagingInformation($xpath)
    {
        $pagingInformation = array();

        $estimatedHitsNode = $xpath->query('/GSP/RES/M');
        if (!$estimatedHitsNode || $estimatedHitsNode->length === 0) {
            return null;
        }
        $pagingInformation['estimatedTotalItemCount'] = (int)$estimatedHitsNode->item(0)->textContent;

        $resultsNodeSet = $xpath->query('/GSP/RES');
        if (!$resultsNodeSet || $resultsNodeSet->length === 0) {
            return $pagingInformation;
        }

        $resultsElement = $resultsNodeSet->item(0);
        if ($resultsElement->hasAttribute('SN') && $resultsElement->hasAttribute('EN')) {
            $startNumber = (int)$resultsElement->getAttribute('SN');
            $endNumber =   (int)$resultsElement->getAttribute('EN');
            $pagingInformation['currentRequestItemRange'] = array (
                'start' => $startNumber,
                'end' => $endNumber,
            );
        } else {
            return null;
        }

        // fix estimatedTotalItemCount if query start does not reflect startNumber
        // which means the estimatedTotalItemCount is wrong
        $queryStartNode = $xpath->query('/GSP/PARAM[@name="start"]');
        if($queryStartNode->length > 0 && $queryStartNode->item(0)->hasAttribute('value')) {
            $queryStartNumber = (int)$queryStartNode->item(0)->getAttribute('value');
            if($startNumber < $queryStartNumber) {
                $pagingInformation['estimatedTotalItemCount'] = $endNumber;
            }
        }
        return $pagingInformation;
    }

    /**
     * Extract the search results from the Google search response
     * @param DomXPath $xpath
     * @return array
     */
    protected function searchResultItems($xpath)
    {
        $items = array();
        $resultElements = $xpath->query('/GSP/RES/R');
        if (!$resultElements || $resultElements->length === 0) {
            return $items;
        }

        foreach ($resultElements as $resultElement) {

            $item = array();
            $index = $resultElement->getAttribute('N');

            if ($resultElement->hasAttribute('MIME')) {
                $item['mimetype'] = $resultElement->getAttribute('MIME');
            }

            if ($title = $xpath->query('T', $resultElement)) {
                $item['title'] = $title->item(0)->textContent;
            }

            if ($summary = $xpath->query('S', $resultElement)) {
                $item['summary'] = $summary->item(0)->textContent;
            }

            if ($url = $xpath->query('U', $resultElement)) {
                $item['url'] = $url->item(0)->textContent;
            }

            if ($hasMoreLikeThis = $xpath->query('HAS/RT', $resultElement)) {
                $item['moreLikeThis'] = true;
            } else {
                $item['moreLikeThis'] = false;
            }

            $item['site'] = $this->extractSite($item['url']);

            $item['index'] = $index;

            $items[] = $item;
        }
        return $items;
    }

    /**
     * Guess site based on url.
     * This could perhaps also be done by setting up some "refinements" in the cse and checking for those in the results.
     *
     * @param string absolute url of item
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
}
