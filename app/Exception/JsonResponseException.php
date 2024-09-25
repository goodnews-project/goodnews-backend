<?php

namespace App\Exception;

class JsonResponseException extends \RuntimeException
{
    public function __construct(string $message = "", int $code = 403)
    {
        
    }
}
