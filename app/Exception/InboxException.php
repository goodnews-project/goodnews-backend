<?php

namespace App\Exception;

use Throwable;

class InboxException extends HttpResponseException
{
    public function __construct(string $message = "", mixed $data = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message,$code,$data,$previous);;
    }
}
