<?php

namespace App\Exception;

use Throwable;

use function Hyperf\Translation\trans;

class AppException extends \Exception
{
    public function __construct(
        string  $message = "",
        public int $httpCode = 403,
        public int $appCode = -1,
        public array $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $appCode, $previous);
    }

    public function tranMessage():string
    {
        $transMessage = trans("message.".$this->getMessage());    
        if($transMessage){
            return $transMessage;
        }
        return $this->message;
    }
}
