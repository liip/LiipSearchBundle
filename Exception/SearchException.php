<?php

namespace Liip\SearchBundle\Exception;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class SearchException extends ServiceUnavailableHttpException
{
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(60, $message, $previous, $code);
    }
}
