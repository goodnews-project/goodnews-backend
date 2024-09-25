<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Admin\InstanceRule;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation as OA;


#[OA\HyperfServer('http')]
class RuleController
{
    #[OA\Get('/_api/v1/rule',summary:"用户注册规则",tags:['用户注册'])]
    public function index()
    {
        return InstanceRule::all();
    }
}
