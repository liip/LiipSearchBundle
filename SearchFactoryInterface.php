<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle;

use Pagerfanta\Pagerfanta;

/**
 * Factory to build pagerfanta instances for search services.
 *
 * Rather than implementation specific results, the pagerfanta instances must return the results in an array as follows:
 *
 * array(
 *     array(
 *        'title' => 'Liip AG | Firma | Jobs | Stellenangebot: JavaScript Developer 60 ...',
 *        'htmlTitle' => '<strong>Liip</strong> AG | Firma ...',
 *        'snippet' => 'Liip AG - Agile Web Development. Mode .',
 *        'htmlSnippet' => '<strong>Liip</strong> AG - <b>Agile</b> Web Development.<br>  Mode &middot; ...',
 *        'url' => 'http://www.liip.ch/company/jobs/javascript-developer.html',
 *        'site' => 'www.liip.ch',
 *        'htmlUrl' => '<a href="http://www.liip.ch/company/jobs/javascript-developer.html">www.liip.ch/...</a>',
 *        'index' => '21', // Position in the total number of search results, 1 based.
 *        'thumbnail' => 'http://google.com/thumbnail.jpg', // url to thumbnail image for this result, if known
 *        'mimetype' => 'application/pdf', // if the search knows the mime type
 *     ),
 *     array(
 *         //...
 *     ),
 * ),
 */
interface SearchFactoryInterface
{
    /**
     * Create a pagerfanta instance for a search.
     *
     * @param string $query Query string as typed by the user.
     * @param string $lang  Request language which the factory may use to restrict the results.
     *
     * @return Pagerfanta A pagerfanta ready for this search.
     */
    public function getPagerfanta($query, $lang);
}
