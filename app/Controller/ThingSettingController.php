<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\ThingSetting;
use App\Model\User;
use App\Request\ThingSettingRequest;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class ThingSettingController extends AbstractController
{
    #[OA\Post("/_api/v1/settings/preferences", summary:"首选项保存", tags:['首选项'])]
    #[OA\Parameter(name: ThingSetting::VAR_DISPLAY_MEDIA, description: '敏感内容设置 1 隐藏被标记为敏感内容的媒体 2 显示所有的媒体 3 隐藏所有媒体', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_DEFAULT_PRIVACY, description: '嘟文默认可见范围 1 公开 4 不公开 2 仅关注者', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_DEFAULT_SENSITIVE, description: '总是将我发送的媒体文件标记为敏感内容', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_SHOW_APPLICATION, description: '展示你用来发嘟的应用', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_USE_BLURHASH, description: '将隐藏媒体显示为彩色渐变', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_EXPAND_SPOILERS, description: '始终展开具有内容警告的嘟文', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_PUBLISH_LANGUAGE, description: '发布语言 null-与界面语言一致', in : 'body', required: false)]
    #[OA\Parameter(name: ThingSetting::VAR_FILTER_LANGUAGE, description: '语言过滤, 数组 null-显示所有语言', in : 'body', required: false, example: '["zh-cn", "en"]')]
    #[OA\Response(
        response: 201,
        description: '操作成功'
    )]
    #[Middleware(AuthMiddleware::class)]
    public function store(ThingSettingRequest $thingSettingRequest)
    {
        $payload = $thingSettingRequest->validated();
        $account = Account::findOrFail(Auth::account()['id']);

        $account_id = $account->id;
        $user_id = $account->user->id;
        foreach ($payload as $var => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            ThingSetting::updateOrCreate(['var' => $var, 'thing_type' => Account::class, 'thing_id' => $account_id], compact('value'));
            ThingSetting::updateOrCreate(['var' => $var, 'thing_type' => User::class, 'thing_id' => $user_id], compact('value'));
        }

        return $this->response->raw(null)->withStatus(201);
    }

    #[OA\Get("/_api/v1/settings/preferences/get", summary:"首选项获取", tags:['首选项'])]
    public function get()
    {
        $account = Account::findOrFail(Auth::account()['id']);
        return $account->settingMap;
    }
}
