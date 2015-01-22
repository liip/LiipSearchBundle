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

use Liip\SearchBundle\SearchInterface;

/**
 * Adapter for google custom search triggered from javascript.
 *
 * This is a dummy client doing nothing but returning an empty array to make the controller happy.
 */
class GoogleCseClient implements SearchInterface
{
    /**
     * Dummy implementation.
     *
     * {@inheritDoc}
     */
    public function search($query, $offset = null, $limit = null, $lang = false, $options = array())
    {
        return array();
    }
}
