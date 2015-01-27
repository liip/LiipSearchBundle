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

use Liip\SearchBundle\Exception\SearchException;

/**
 * Contract for adapters to search services.
 */
interface SearchInterface
{
    /**
     * Query the search service.
     *
     * Returns an array of the form:
     *
     * array(
     *     information => array( // (might not exist)
     *         'spellingSuggestions' => array(
     *             'suggestedSpelling1',
     *             'suggestedSpelling2',
     *              // etc.
     *         ),
     *         'paging' => array(
     *             'estimatedTotalItemCount' => 93,
     *             'currentRequestItemRange' => array(
     *                 'start' => 21   // note: counts from 1, not 0
     *                 'end' => 30
     *             )
     *         ),
     *     ),
     *     items => array(
     *         array(
     *            'title' => 'Liip AG | Firma | Jobs | Stellenangebot: JavaScript Developer 60 ...',
     *            'htmlTitle' => '<strong>Liip</strong> AG | Firma ...',
     *            'snippet' => 'Liip AG - Agile Web Development. Mode .',
     *            'htmlSnippet' => '<strong>Liip</strong> AG - <b>Agile</b> Web Development.<br>  Mode &middot; ...',
     *            'url' => 'http://www.liip.ch/company/jobs/javascript-developer.html',
     *            'site' => 'www.liip.ch',
     *            'htmlUrl' => '<a href="http://www.liip.ch/company/jobs/javascript-developer.html">www.liip.ch/...</a>',
     *            'index' => '21', // Position in the total number of search results, 1 based.
     *            'thumbnail' => 'http://google.com/thumbnail.jpg', // url to thumbnail image for this result, if known
     *            'mimetype' => 'application/pdf', // if the search knows the mime type
     *         ),
     *         array(
     *             //...
     *         ),
     *     ),
     * )
     *
     * Items is an empty list if no results are found or you went beyond the last result with $offset.
     *
     * @param string         $query   Query string as typed by the user.
     * @param string|null    $offset  First result to return, defaults to first result. This is 1-based, not 0-based!
     * @param string|null    $limit   Maximum number of results to return, implementation chooses reasonable default.
     * @param string|boolean $lang    If set, restrict results to specified language.
     * @param array          $options Search engine specific options to pass through.
     *
     * @return array Search result meta information and details.
     *
     * @throws SearchException If the search service does not work.
     */
    public function search($query, $offset = null, $limit = null, $lang = false, $options = array());
}
