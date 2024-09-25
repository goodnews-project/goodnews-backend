<?php

declare(strict_types=1);

namespace App\Aspect;

use Hyperf\Context\Context;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Paginator\Paginator;

#[Aspect]
class BuilderPaginateAspect extends AbstractAspect
{
    public $response ;
    public array $classes = [
        "Hyperf\Database\Model\Builder::paginate"
    ];

    public function __construct(protected ContainerInterface $container)
    {
        $this->response = $container->get(ResponseInterface::class);
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $request = $this->container->get(RequestInterface::class);
        $route = $request->getAttribute(Dispatched::class)->handler->route;
        if( str_starts_with($route,'/_api/v1/reward') || str_starts_with($route,'/_api/v1/withdraw') || str_starts_with($route,'/_api/v1/search')){
            return $proceedingJoinPoint->process();
        }

        if(!str_starts_with($route,'/api/v1') && !str_starts_with($route,'/api/v2') && !str_starts_with($route,'/_api/v1')){
            return $proceedingJoinPoint->process();
        }
        $args  = $proceedingJoinPoint->getArguments();

        $query = $request->all();
        if(isset($query['max_id'])){
            $proceedingJoinPoint->getInstance()->where('id','<',$query['max_id']);
        }
        if(isset($query['min_id'])){
            $proceedingJoinPoint->getInstance()->where('id','>',$query['min_id']); 
        }
        if(isset($query['since_id'])){
            $proceedingJoinPoint->getInstance()->where('id','>',$query['since_id']); 
        } 
        if(isset($query['limit'])){
            $items = $proceedingJoinPoint->getInstance()->take($query['limit'] ?? 30)->get($args['columns']??['*']);
        }else{
            $items = $proceedingJoinPoint->getInstance()->take($args['perPage']?? 30)->get($args['columns']??['*']);
        }

        $query = $request->all();
        if($items->count() >0){
            $this->response->withHeader("link",sprintf(
                '<%s>; rel="next" <%s>',
                $request->url().'?'.http_build_query(array_merge($query,['max_id'=> $items->first()->id])),
                $request->url().'?'.http_build_query(array_merge($query,['min_id'=> $items->last()->id])),
            ));
        }
        //Link: <https://mastodon.example/api/v1/notifications?max_id=34975535>; rel="next", <https://mastodon.example/api/v1/notifications?min_id=34975861>;
        
        
        
            
        return $this->container->make(LengthAwarePaginatorInterface::class, [
            'items'=> $items,
            'total'=> 0,
            'perPage' => $args['perPage'] ?? 20,
        ]);
    }
}
