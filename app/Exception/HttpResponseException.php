<?php

namespace App\Exception;

use \Throwable;


class HttpResponseException extends \RuntimeException
{
    public function __construct(
        $message = '',
        $code = 0,
        public mixed $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
