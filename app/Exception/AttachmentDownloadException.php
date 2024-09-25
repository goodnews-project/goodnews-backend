<?php

namespace App\Exception;

class AttachmentDownloadException extends JsonResponseException
{
    public function __construct(string $message = "")
    {
       parent::__construct($message,403);
    }
}
