<?php

namespace Q8Intouch\Q8Query\Core\Exceptions;
use Exception;
use Throwable;

class NotAuthorizedException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
