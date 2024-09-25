<?php

declare(strict_types=1);

namespace App\Aspect;

use Hyperf\Context\Context;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Paginator\Paginator;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Psr\Http\Message\ServerRequestInterface;

#[Aspect]
class PaginatorListenerAspect extends AbstractAspect
{
    public array $classes = [
        "Hyperf\Paginator\Listener\PageResolverListener::process"
    ];

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        Paginator::currentPageResolver(function ($pageName = 'page') {
            if (!ApplicationContext::hasContainer()
                || !interface_exists(RequestInterface::class)
                || !Context::has(ServerRequestInterface::class)
            ) {
                return 1;
            }

            $container = ApplicationContext::getContainer();
            $page = $container->get(RequestInterface::class)->input($pageName);
            if (!$page){
                $page = $container->get(RequestInterface::class)->route($pageName);
            }


            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return (int)$page;
            }

            return 1;
        });

        Paginator::currentPathResolver(function () {
            $default = '/';
            if (!ApplicationContext::hasContainer()
                || !interface_exists(RequestInterface::class)
                || !Context::has(ServerRequestInterface::class)
            ) {
                return $default;
            }

            $container = ApplicationContext::getContainer();
            return $container->get(RequestInterface::class)->url();
        });
    }
}
