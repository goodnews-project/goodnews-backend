<?php

declare(strict_types=1);

namespace App\Aspect;

use App\Aspect\Annotation\ExecTimeLogger;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Logger\LoggerFactory;

#[Aspect]
class ExecTimeLoggerAspect extends AbstractAspect
{
    #[Inject]
    protected LoggerFactory $logger;

    public array $annotations = [
        ExecTimeLogger::class,
    ];
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $collector = $proceedingJoinPoint->getAnnotationMetadata();
        $execTimeLogger = $collector->method[ExecTimeLogger::class];
        $logger = $this->logger->get($execTimeLogger->name,$execTimeLogger->group);

        
        $class = $proceedingJoinPoint->className;
        $method = $proceedingJoinPoint->methodName;
        $params = $proceedingJoinPoint->arguments['keys'];


        $t1 = microtime(true);
        $process = $proceedingJoinPoint->process();
        $time = round(microtime(true) - $t1, 5);

        if(!$execTimeLogger->logParams){
            $params = [];
        }
        if(is_array($execTimeLogger->logParams)){
            $params = Arr::only($params,$execTimeLogger->logParams);
        }

        $logger->info("{$class}::{$method} Exec: {$time}s",$params);

        return $process;
    }
}
