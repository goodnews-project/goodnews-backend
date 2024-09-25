<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Account;
use App\Service\Auth;
use Hyperf\Swagger\Annotation as OA;


#[OA\HyperfServer('http')]
class RecommendController extends AbstractController
{
    #[OA\Get("/_api/v1/recommend/account",summary:'account列表',tags:['account'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function account()
    {
        return Account::isFollow(Auth::account())->orderBy('followers_count','desc')->paginate(10); 
    } 
}
