<?php

namespace Recca0120\Upload\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ChunkedResponseException extends Exception
{
    protected $headers;

    public function __construct($headers = [], $code = Response::HTTP_CREATED)
    {
        parent::__construct('', $code);
        $this->headers = $headers;
    }

    public function getResponse()
    {
        return new Response(null, $this->getCode(), $this->headers);
    }
}