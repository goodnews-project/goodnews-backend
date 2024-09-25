<?php

namespace App\Nsq\Consumer;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;
use App\Util\Log;
class BaseConsumer extends AbstractConsumer
{
    public function consume(Message $payload) : null|string
    {
        $body = $payload->getBody();
        if (empty($body)) {
            Log::warning(get_called_class().'body is emtpy', compact('body'));
            return Result::DROP;
        }
        

        if(!json_validate($body)){
            Log::error(get_called_class().' data invalid', compact('body'));
            return Result::DROP;
        }

        $data = json_decode($body,true);
        Log::info(get_called_class().' start', compact('data'));
        try{
            return $this->handle($data);
        }catch(\Exception $e){
            Log::error(get_called_class().' error'. $e->getMessage(),$e->getTrace());
        }catch(\Error $error){
            Log::error($error->getMessage(),$error->getTrace()); 
        }
        
    }
}
