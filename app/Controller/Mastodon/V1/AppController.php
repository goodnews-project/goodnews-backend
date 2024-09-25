<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Middleware\OAuthClientMiddleware;
use App\Model\Account;
use App\Model\Admin\InstanceRule;
use App\Model\Setting;
use App\Request\Mastodon\AppsRequest;
use App\Request\Mastodon\RegRequest;
use App\Resource\Mastodon\ApplicationResource;
use App\Resource\Mastodon\InstanceRuleCollection;
use App\Resource\Mastodon\TokenResource;
use App\Resource\Mastodon\V1\InstanceResource;
use App\Service\Activitypub\ActivitypubService;
use App\Service\Auth;
use App\Service\UserService;
use GuzzleHttp\Client;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\AuthorizationServerFactory;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Controller\ConvertsPsrResponses;
use Richard\HyperfPassport\Controller\HandlesOAuthErrors;
use function Hyperf\Support\env;

#[OA\HyperfServer('http')]
class AppController extends AbstractController
{
    use HandlesOAuthErrors, ConvertsPsrResponses;

    #[Inject]
    protected UserService $userService;

    #[Inject]
    protected ClientRepository $clientRepository;

    #[Inject]
    private ClientFactory $clientFactory;

    #[Inject]
    protected AuthorizationServerFactory $authorizationServerFactory;

    #[OA\Post(path:'/api/v1/apps',summary:'注册应用',tags:['mastodon'])]
    #[OA\Parameter(name: 'client_name', description: '推文内容', in : 'query', required: true)]
    #[OA\Parameter(name: 'redirect_uris', description: '授权后应该将用户重定向到哪里', in : 'query', required: true, example: 'urn:ietf:wg:oauth:2.0:oob,这个会展示授权码')]
    #[OA\Parameter(name: 'scopes', description: '空格分隔的作用域列表', in : 'query')]
    #[OA\Parameter(name: 'website', description: '应用程序主页的URL', in : 'query')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function apps(AppsRequest $appsRequest)
    {
        $payload = $appsRequest->validated();

        $client = $this->clientRepository->create(null, $payload['client_name'], $payload['redirect_uris'], 'oauth_users');
        $client->scopes = $payload['scopes'] ?? '';
        $client->website = $payload['website'] ?? '';
        $client->save();

        return ApplicationResource::make($client);
    }

    #[OA\Post(path:'/api/v1/accounts',
        description: '注册用户:Register an account',
        summary:'https://docs.joinmastodon.org/methods/accounts/#create',tags:['mastodon'])]
    #[Middleware(OAuthClientMiddleware::class)]
    public function createAccount(RegRequest $regRequest)
    {
        $payload = $regRequest->validated();
        $payload['clientIp'] = $this->getClientIp();
        $payload['display_name'] = $payload['username'];
        $client = Auth::passportClient();
        try {
            $this->userService->reg($payload);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage(), 'details' => ['email' => [['error' => 'ERR_TAKEN', 'description' => $e->getMessage()]]]])->withStatus(422);
        }
        $data = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => $client->scopes,
            'username' => $payload['username'],
            'password' => $payload['password'],
        ];

        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent' => ActivitypubService::getUa(),
            ],
        ]);
        $r = $client->post(getApHostUrl().'/oauth/token', ['json' => $data]);
        if ($r->getStatusCode() != 200) {
            return $this->response->json(['error' => 'The access token is invalid']);
        }
        return TokenResource::make(json_decode($r->getBody()->getContents(), true));
    }

    #[OA\Get(path:'/api/v1/instance',
        description: 'Obtain general information about the server.',
        summary:'https://docs.joinmastodon.org/methods/instance/#v1',tags:['mastodon'])]
    public function instance()
    {
        $settings = Setting::whereNull('settingable_id')->whereIn('key',[
            'site_title',
            'site_short_description',
            'site_contact_username',
            'site_contact_email'
        ])->pluck('value','key');
        if (empty($settings['site_contact_username'])) {
            return [];
        }
        $contactAccount  =  Account::where('acct', $settings['site_contact_username'])->firstOrFail();
        $rules = InstanceRule::all();
        return InstanceResource::make(compact('rules', 'contactAccount', 'settings'));
    }

    #[OA\Get(path:'/api/v1/instance/rules',summary:'获取规则列表',tags:['mastodon'])]
    public function rules()
    {
        return (new InstanceRuleCollection(InstanceRule::all()));
    }
   
}
