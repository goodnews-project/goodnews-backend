<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Middleware\AuthMiddleware;
use App\Model\Setting;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
// #[Middleware(AuthMiddleware::class)]
class SettingController extends AbstractController
{

    #[OA\Get('/admin/api/setting')]
    public function index()
    {
        $keys = explode(',',$this->request->input('keys',''));
        return Setting::whereNull('settingable_id')->whereIn('key',$keys)
                ->get()->keyBy('key');
    }

    #[OA\Put('/admin/api/setting')]
    public function store()
    {
        
    }
}
