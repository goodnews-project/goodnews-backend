<?php

declare(strict_types=1);

namespace App\Command;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\UserController;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Account;
use App\Model\AccountSubscriberLog;
use App\Model\Admin\IpBlock;
use App\Model\Admin\Role;
use App\Model\Attachment;
use App\Model\Bookmark;
use App\Model\Conversation;
use App\Model\DirectMessage;
use App\Model\Filter;
use App\Model\Follow;
use App\Model\FollowRecommendation;
use App\Model\Hashtag;
use App\Model\Notification;
use App\Model\PayLog;
use App\Model\Poll;
use App\Model\Relay;
use App\Model\Report;
use App\Model\Setting;
use App\Model\Status;
use App\Model\StatusesFave;
use App\Model\StatusesMention;
use App\Model\StatusHashtag;
use App\Model\StatusUnlockLog;
use App\Model\ThingSetting;
use App\Model\User;
//use App\Model\WalletAddressLog;
use App\Nsq\Consumer\ActivityPub\Trait\ApRepository;
use App\Nsq\Queue;
use App\Request\Admin\UserRequest;
use App\Resource\Mastodon\ConversationResource;
use App\Resource\Mastodon\FilterKeywordCollection;
use App\Resource\Mastodon\NotificationResource;
use App\Resource\Mastodon\StatusResource;
use App\Resource\StatusPaginateResource;
use App\Service\AccountService;
use App\Service\Activitypub\ActivitypubService;
use App\Service\Activitypub\DeleteRemoteStatus;
use App\Service\Activitypub\ProcessInboxValidator;
use App\Service\AttachmentService;
use App\Service\AttachmentServiceV2;
use App\Service\AttachmentServiceV3;
use App\Service\Auth;
use App\Service\CacheService;
use App\Service\EsService;
use App\Service\ImportAndExportService;
use App\Service\OpenGraphService;
use App\Service\RedisService;
use App\Service\SearchService;
use App\Service\SettingService;
use App\Service\StatusesService;
use App\Service\TimelineService;
use App\Service\UrisService;
use App\Service\UserService;
use App\Service\WebfingerService;
use App\Util\ActivityPub\Helper;
use App\Util\ActivityPub\HttpSignature;
use App\Util\ActivityPub\Inbox;
use App\Util\CidrMatch;
use App\Util\Image\ImageStream;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use App\Util\Log;
use App\Util\Media\Blurhash;
use Aws\S3\S3Client;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Hyperf\Cache\Cache;
use Hyperf\Collection\Arr;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Coroutine\Exception\ParallelExecutionException;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\HandlerStackFactory;
use Hyperf\Nsq\Nsq;
use Hyperf\Nsq\Nsqd\Api;
use Hyperf\Redis\Redis;
use Hyperf\Server\ServerFactory;
use Hyperf\Stringable\Str;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Jaytaph\TypeArray\TypeArray;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;
use OCA\Social\Service\SignatureService;
use PHPUnit\Util\Type;
use Psr\Container\ContainerInterface;
use Hyperf\Coroutine\Coroutine;

use ReflectionClass;
use stdClass;
use Swoole\Table;
use Swoole\WebSocket\Server;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\HttpClient\HttpOptions;
use function Hyperf\Support\env;
use function Hyperf\Support\make;
use function Hyperf\Translation\trans;
use function Swoole\Coroutine\Http\get;
use Hyperf\Elasticsearch\ClientBuilderFactory;

#[Command]
class Test extends HyperfCommand
{
    use ApRepository;
    const SCAN_RE = "/(?<=[^[:alnum:]:]|\n|^):([a-zA-Z0-9_]{2,}):(?=[^[:alnum:]:]|$)/x";


    #[Inject]
    protected RedisService $redisService;

    #[Inject]
    protected AttachmentServiceV3 $attachmentService;

    #[Inject]
    protected Filesystem $filesystem;

    #[Inject]
    private StatusesService $statusesService;

    #[Inject]
    private AccountService $accountService;

    #[Inject]
    private ClientFactory $clientFactory;

    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;

    #[Inject]
    protected ActivitypubService $activitypubService;

    #[Inject]
    protected ImportAndExportService $importAndExportService;

    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected UserService $userService;

    #[Inject]
    protected TimelineService $timelineService;

    #[Inject]
    private TranslatorInterface $translator;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('demo');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, '这里是对这个参数的解释']
        ];
    }

    public function subscribe($redis, $chan, $msg)
    {
        var_dump($chan, $msg);
        return 'ok';
    }

    public function changeWalletAddress($accountId, $payload)
    {
        $walletAddress = $payload['wallet_address'] ?? null;
        if (empty($walletAddress)) {
            $payload['wallet_address'] = null;
            return $payload;
        }
//        $walletAddressLog = WalletAddressLog::where('address', $walletAddress)->first();
//        if (empty($walletAddressLog)) {
//            WalletAddressLog::create([
//                'account_id' => $accountId,
//                'address' => $walletAddress,
//            ]);
//        }
        $payload['wallet_address'] = $walletAddress;
        return $payload;
    }

    public function handle()
    {
        $client = $this->clientFactory->create([
            'timeout' => 15
        ]);
//        $r = $client->put('https://instance.good.news/register', ['form_params' => ['host' => 'google.com']]);
        $r = $client->post('https://instance.good.news/version-check', ['form_params' => ['version' => '1.0.1']]);
        var_dump($r->getBody()->getContents());
        die;

//        $account = false;
//        $statusList = $this->timelineService->thisServer($account);
//        var_dump($statusList);die;

//        $tx = "0x6f159c0a7c6de7fb64b85c428642c39253f42f608460de9e965fac7e33ab970b";
//        $log = $this->ethService->getSubLog($tx);
//        var_dump($log);die;
//        $orderId = preg_replace('/[^0-9]/', '', $log->orderId);
//        var_dump($orderId);die;
//
//        $r = PayLog::create(['hash' => $log->hash, 'send_addr' => $log->fromAddr, 'recv_addr' => $log->authorAddr,
//            'fee' => $log->amount, 'order_id' => $log->orderId, 'block' => $log->block, 'paid_at' => Carbon::createFromTimestamp($log->timestamp)]);
//        var_dump($r);die;

//        $account = Account::findOrFail(2);
//        $account2 = Account::findOrFail(3);
//        $r = $this->accountService->details($account->acct, $account2);
//        var_dump($r);die;
//        var_dump(strlen('6000000000000000000'));die;
//        var_dump(strtolower('0xfAA555517A5c9FB93A0Ae317f2A9ab15e36BcD78') == '0xfaa555517a5c9fb93a0ae317f2a9ab15e36bcd78');die;
//        $id = "1808102044743951342";

        $log = $this->ethService->getUnlockLogByHash('0xbb5ef247e211d80f1455bab0c99a2cd63bd5a433b5dff44610df43466132e5aa');
//        $r = PayLog::create(['hash' => $log->hash, 'send_addr' => $log->fromAddr, 'recv_addr' => $log->authorAddr,
//            'fee' => $log->amount, 'order_id' => $log->orderId, 'block' => $log->block, 'paid_at' => Carbon::now()]);
        var_dump($log);die;
//        var_dump(mb_check_encoding()$r->orderId == $id);die;

//        $r = Account::where('id',2)->with('user:id,account_id,role_id')
//            ->withCount(['tweets'])->first()->toArray();
//        var_dump($r['user']);
//        die;
//        var_dump($status->count(), $status->selectRaw('count(distinct account_id) as peoples')->value('peoples'), $status->where('created_at', '>', Carbon::today())->count());die;

        $tx = "0x24390ccd8024c58b542568551fb39cf527f60ab76f115f5debe2f877ce325391";
//
//        $receipt = $this->ethService->getTxReceipt($tx);
//
//        var_dump($receipt);die;
        $tx = $this->ethService->getTxByHash($tx);
        var_dump($tx);die;

        Account::where('id', 2)->where('status_count', '>', 0)?->decrement('status_count');die;
        $account = Account::find(2);

        $r = Status::withInfo($account)->limit(1)->first();
        var_dump($r->toArray());die;



        // create status for test07
        $account = Account::findOrFail(7);
        $data = $this->statusesService->create($account['id'], uniqid('test_data_success'), [
            'published_at' => Carbon::now()->toDateTimeString(),
            'not_mentions' => true,
        ]);
        $status = Status::findOrFail($data['id']);
        $this->sendRelay($status, function ($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl) {
            return $this->getCreateOrPollApData($status, $account, $to, $cc, $inReplyToUri, $tags, $inboxUrl);
        }, __METHOD__);
        die;

        $s = '{"username":"test04","obj":{"@context":["https://www.w3.org/ns/activitystreams",{"ostatus":"http://ostatus.org#","atomUri":"ostatus:atomUri","inReplyToAtomUri":"ostatus:inReplyToAtomUri","conversation":"ostatus:conversation","sensitive":"as:sensitive","toot":"http://joinmastodon.org/ns#","votersCount":"toot:votersCount","blurhash":"toot:blurhash","focalPoint":{"@container":"@list","@id":"toot:focalPoint"}}],"id":"https://ruby.good.news/users/hz/statuses/112161746076036928/activity","type":"Create","actor":"https://ruby.good.news/users/hz","published":"2024-03-26T11:27:04Z","to":["https://ruby.good.news/users/hz/followers"],"cc":[],"object":{"id":"https://ruby.good.news/users/hz/statuses/112161746076036928","type":"Note","summary":null,"inReplyTo":null,"published":"2024-03-26T11:27:04Z","url":"https://ruby.good.news/@hz/112161746076036928","attributedTo":"https://ruby.good.news/users/hz","to":["https://ruby.good.news/users/hz/followers"],"cc":[],"sensitive":false,"atomUri":"https://ruby.good.news/users/hz/statuses/112161746076036928","inReplyToAtomUri":null,"conversation":"tag:ruby.good.news,2024-03-26:objectId=1638137:objectType=Conversation","content":"<p>video test</p>","contentMap":{"zh":"<p>video test</p>"},"attachment":[{"type":"Document","mediaType":"video/mp4","url":"https://file.aaaaa.bet/media_attachments/files/112/161/744/772/698/276/original/f1a9a08f1eabbbcb.mp4","name":null,"blurhash":"UGEo[DRj9F-;x]-pR*D%4UShxZV[4nt7%MjY","width":272,"height":270}],"tag":[],"replies":{"id":"https://ruby.good.news/users/hz/statuses/112161746076036928/replies","type":"Collection","first":{"type":"CollectionPage","next":"https://ruby.good.news/users/hz/statuses/112161746076036928/replies?only_other_accounts=true&page=true","partOf":"https://ruby.good.news/users/hz/statuses/112161746076036928/replies","items":[]}}}},"headers":{"host":["activitypub.good.news"],"connection":["close"],"content-length":["1812"],"accept-encoding":["gzip, br"],"x-forwarded-for":["173.255.192.114"],"cf-ray":["86a6ca145db14656-DFW"],"x-forwarded-proto":["https"],"cf-visitor":["{\"scheme\":\"https\"}"],"user-agent":["http.rb/5.1.1 (Mastodon/4.1.3; +https://ruby.good.news/)"],"date":["Tue, 26 Mar 2024 11:27:04 GMT"],"digest":["SHA-256=mhEeopYPU/kc/pYU0N+WiVqBU+1BlDyd/k6VYyXhb7I="],"content-type":["application/activity+json"],"collection-synchronization":["collectionId=\"https://ruby.good.news/users/hz/followers\", digest=\"e44b73d61a90cc5ebe33f5fd0cca16552002e40a02745dca030c020d0b933dca\", url=\"https://ruby.good.news/users/hz/followers_synchronization\""],"signature":["keyId=\"https://ruby.good.news/users/hz#main-key\",algorithm=\"rsa-sha256\",headers=\"(request-target) host date digest content-type collection-synchronization\",signature=\"nS2Br2Q+PrHH5YEXHgtFgXoGdwJHUCFZHRlV3AtSzgTVmD4+FLnr6tCxwK4grIYMP+o7Z9h4g+EOIV0EIz4VYMxTeiq85c5eMYceDzMeZ6Nj9Kbmqspv9hrZI1yM5wAYC7CUXRtLtuat05ejNrJO0mid+4qZk5A9K4CZPow1nWkOx+a4BdyEQ1Ou2fpKzThnitJoQAvwX6N/mX+mEUvRoOeL+537L3k0DBEVgd9UYZ2pw6WRJsNIDZfGqrPoHJ/2XZ/UQsD8Fq6a7rIwSJjat84aZ2LIaXxlv2uJBGpJyvpJhpPhD7mFu71mDGBnqZ9JoDsIEJljzOoe4rWO6UV4nw==\""],"cf-connecting-ip":["173.255.192.114"],"cdn-loop":["cloudflare"],"cf-ipcountry":["US"]}}';
        $arr = json_decode($s, true);
        $username = $arr['username'];
        $account = Account::whereNull('domain')->where('username', $username)->first();
        $headers = $arr['headers'];
        $payload = $arr['obj'];
        Helper::setValidationFactory($this->validationFactory);
        \Hyperf\Support\make(Inbox::class, compact('headers', 'account', 'payload'))->handle();
    }

//    protected function canonicalize(TypeArray $data): string
//    {
//        try {
//            $ret = jsonld_normalize(
//                $this->array2object($data),
//                [
//                    'algorithm' => 'URDNA2015',
//                    'format' => 'application/nquads',
//                ]
//            );
//        } catch (\Throwable $e) {
//            throw new \RuntimeException('Cannot canonicalize message', 0, $e);
//        }
//
//        return strval($ret);
//    }
//
//    protected function hash($data): string
//    {
//        return hash('sha256', $this->canonicalize(new TypeArray($data)));
//    }
//
//    protected function array2object(TypeArray $data): mixed
//    {
//        $json = json_encode($data->toArray());
//        if (!$json) {
//            $json = '';
//        }
//
//        return json_decode($json, false, 512, JSON_THROW_ON_ERROR);
//    }



}
