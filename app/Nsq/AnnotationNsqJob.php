<?php

namespace App\Nsq;

use Hyperf\AsyncQueue\Environment;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\UnCompressInterface;
use Hyperf\Nsq\Result;

class AnnotationNsqJob
{
    public function __construct(public string $class, public string $method, public array $params)
    {
        
    }

    public function handle()
    {
        $container = ApplicationContext::getContainer();

        $class = $container->get($this->class);

        $params = [];
        foreach ($this->params as $key => $value) {
            if ($value instanceof UnCompressInterface) {
                $value = $value->uncompress();
            }
            $params[$key] = $value;
        }
        // var_dump($this->method);
        $container->get(Environment::class)->setAsyncQueue(true);
        $class->{$this->method}(...$params);
        return Result::ACK;
    }
}
