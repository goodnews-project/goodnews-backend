<?php

declare(strict_types=1);

namespace App\Aspect;

use App\Nsq\AnnotationNsqJob;
use App\Nsq\NsqQueueMessage;
use Hyperf\AsyncQueue\Environment;
use Hyperf\Codec\Packer\PhpSerializerPacker;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Nsq\Nsq;

use function Hyperf\Support\make;

#[Aspect]
class NsqQueueAspect extends AbstractAspect
{
    public array $annotations = [
        NsqQueueMessage::class,
    ];

    public function __construct(
        protected ContainerInterface $container
    )
    {

    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $env = $this->container->get(Environment::class);
        if ($env->isAsyncQueue()) {
            $proceedingJoinPoint->process();
            return;
        }


        $class = $proceedingJoinPoint->className;
        $method = $proceedingJoinPoint->methodName;

        $arguments = [];
        $parameters = $proceedingJoinPoint->getReflectMethod()->getParameters();
        foreach ($parameters as $parameter) {
            $arg = $proceedingJoinPoint->arguments['keys'][$parameter->getName()];
            if ($parameter->isVariadic()) {
                $arguments = array_merge($arguments, $arg);
            } else {
                $arguments[] = $arg;
            }
        }
        $nsq = make(Nsq::class);
        $job = make(AnnotationNsqJob::class, [$class, $method, $arguments]);
        $nsq->publish('queue-message',(new PhpSerializerPacker)->pack($job),0);

        // var_dump(111);
    }
}
