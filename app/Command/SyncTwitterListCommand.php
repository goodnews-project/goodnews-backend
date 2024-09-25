<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Account;
use App\Model\Attachment;
use App\Model\Follow;
use App\Model\Notification;
use App\Model\Status;
use App\Nsq\Queue;
use App\Service\AttachmentServiceV2;
use App\Service\StatusesService;
use App\Service\UrisService;
use App\Util\ActivityPub\Helper;
use Carbon\Carbon;
use DateTimeImmutable;
use GuzzleHttp\Psr7\StreamWrapper;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

use function Hyperf\Support\env;

#[Command]
#[Crontab(name: "sync-twitter", rule: "* * * * *", callback: "executeCrontab", memo: "")]
class SyncTwitterListCommand extends HyperfCommand
{
    #[Inject]
    protected AttachmentServiceV2 $attachmentService;
    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected ClientFactory $client;


    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('sync:twitter');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }
    public function executeCrontab()
    {
        if (env('ENABLE_SYNC_TWITTER')) {
            $this->output = new ConsoleOutput();
            $this->handle();
        }
    }
    public function handle()
    {
        $this->crawl("http://96.126.126.86/news.json");
        $this->crawl("http://96.126.126.86/it.json");
    }

    public function writeJsonCache($url,$content)
    {
        $type= parse_url($url)['path'];
        $date= Carbon::now()->toDateString();
        $time= Carbon::now()->toTimeString();
        $path = "storage/twitter-json{$type}/{$date}";
        var_dump($path);
        if (!is_dir($path)) {
            // dir doesn't exist, make it
            mkdir($path,recursive:true);
        }

        file_put_contents($path."/{$time}",$content);

    }
    public function crawl($url)
    {
        $tweetsRespContent = file_get_contents($url);
        $this->writeJsonCache($url,$tweetsRespContent);
        $tweetsResp = json_decode($tweetsRespContent, true);
        if (empty($tweetsResp['data']['list']['tweets_timeline']['timeline']['instructions'][0]['entries'])) {
            $this->error("错误的接口格式");
            return;
        }

        $tweetsList = $tweetsResp['data']['list']['tweets_timeline']['timeline']['instructions'][0]['entries'];
        foreach ($tweetsList as $tweet) {
            if (!str_starts_with($tweet['entryId'], "tweet-")) {
                continue;
            }
            $this->handleTweet($tweet['content']['itemContent']['tweet_results']['result']);
        }
    }
    public function handleTweet($tweet)
    {
        $reply = [];
        if ($tweet['__typename'] == "TweetWithVisibilityResults") {
            $user = $tweet['tweet']['core']['user_results']['result'];
            $content = $tweet['tweet'];
        } else {

            $user = $tweet['core']['user_results']['result'];
            $content = $tweet;

            // 处理user
            // 处理引用
            if (!empty($tweet['quoted_status_result']['result'])) {
                $this->info("handle tweet quoted");
                [$user, $content] = $this->handleTweet($tweet['quoted_status_result']['result']);
            }
            // 处理retweet
            if (!empty($tweet['legacy']['retweeted_status_result']['result'])) {
                $this->info("handle retweet");
                [$user, $content] = $this->handleTweet($tweet['legacy']['retweeted_status_result']['result']);
            }
        }

        $account = $this->handleUser($user);
        $this->handleTweetContent($account, $content, $reply);
        return [$user, $content];
    }

    public function handleUser($user)
    {
        $account = Account::where('acct', $user['legacy']['screen_name'])->first();
        $avatar = str_replace("_normal", "", $user['legacy']['profile_image_url_https']);
        $avatar = str_replace("_normal", "", $user['legacy']['profile_image_url_https']);
        if ($account) {
            $account->update([
                'username'          => $user['legacy']['screen_name'],
                'domain'            => null,
                'display_name'      => $user['legacy']['name'],
                'note'              => $user['legacy']['description'],
                'profile_image'     => $user['legacy']['profile_banner_url'] ?? null,
                'avatar'            => $avatar,
                'avatar_remote_url' => $avatar,
                'is_activate'       => true,
            ]);
            return $account;
        }
        $pkiConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $pki = openssl_pkey_new($pkiConfig);
        openssl_pkey_export($pki, $pkiPrivate);
        $pkiPublic = openssl_pkey_get_details($pki);
        $account = Account::create([
            'acct'              => $user['legacy']['screen_name'],
            'username'          => $user['legacy']['screen_name'],
            'domain'            => null,
            'display_name'      => $user['legacy']['name'],
            'note'              => $user['legacy']['description'],
            'profile_image'     => $user['legacy']['profile_banner_url'] ?? null,
            'avatar'            => $avatar,
            'avatar_remote_url' => $avatar,
            'is_activate'       => true,
            'private_key'       => $pkiPrivate,
            'public_key'        => $pkiPublic['key'],
            'actor_type'        => Account::ACTOR_TYPE_SERVICE,
            'fields'            => [
                ['name' => 'Official', "type" => "PropertyValue", "value" => sprintf("<a href='%s'>%s</a>","https://x.com/{$user['legacy']['screen_name']}","https://x.com/{$user['legacy']['screen_name']}")],
                ['name' => '本机器人服务由 good.news 提供', "type" => "PropertyValue", "value" => sprintf("<a href='%s'>%s</a>","https://good.news/about","https://good.news/about")],
            ]
        ]);

        if ($account->wasRecentlyCreated) {
            // $client = $this->client->create();
            // $response = $client->request('POST', 'https://good.news/_api/v1/remote-follow', [
            //     'form_params' => [
            //         'url' => UrisService::generateURIsForAccount($account->username)['userURI'],
            //         'pwd' => '123!!@$$!$',
            //     ]
            // ]);
            // $content = json_decode($response->getBody()->__toString(), true);
            // $remoteAccount = Helper::accountFirstOrNew($content['uri']);

            // Follow::firstOrCreate([
            //     'account_id'        => $remoteAccount['id'],
            //     'target_account_id' => $account['id']
            // ]);
            // $this->info("create account:{$user['legacy']['screen_name']}");
        }
        return $account;
    }

    public function handleTweetContent(Account $account, $tweet, $replay = null)
    {
        if (empty($tweet['rest_id'])) {
            var_dump($tweet);
        }
        $status = Status::find($tweet['rest_id']);
        if ($status) {
            $this->info("old tweet");
            return;
            // $status->delete();
            // $this->info("re sync {$tweet['rest_id']}");
        }

        // 转推文章去原文拿附件
        $urls = [];
        $attachments = [];
        if (!empty($tweet['legacy']['entities']['media'])) {
            [$urls, $attachments] = $this->handleMedia($tweet['legacy']['entities']['media'], $tweet['rest_id']);
        } else if (!empty($tweet['legacy']['retweeted_status_result']['result']['legacy']['entities']['media'])) {
            [$urls, $attachments] = $this->handleMedia($tweet['legacy']['retweeted_status_result']['result']['legacy']['entities']['media'], $tweet['rest_id']);
        }
        if (!empty($tweet['note_tweet']['note_tweet_results']['result']['text'])) {
            $content = $tweet['note_tweet']['note_tweet_results']['result']['text'];
        } else {
            $content = $tweet['legacy']['full_text'];
        }

        // 取用户
        if (!empty($tweet['legacy']['entities']['user_mentions'])) {
            foreach ($tweet['legacy']['entities']['user_mentions'] as $user) {
                // $this->handleUser([
                //     'legacy' => [
                //         'profile_image_url_https' => "",
                //         'screen_name'             => $user['screen_name'],
                //         'name'                    => $user['name'],
                //         'description'             => 'Not Found',
                //         'profile_banner_url'      => "",
                //     ]
                // ]);
            }
        }

        $content = str_replace($urls, '', $content);
        // replace RT @x
        $content = preg_replace('/^(RT .*: )(.*)/', '', $content);

        if (empty($content) && empty($attachments)) {
            $this->error("empty status {$tweet['rest_id']}");
            return;
        }
        $status = $this->statusesService->create($account['id'], $content, [
            'id'           => $tweet['rest_id'],
            'published_at' => Carbon::parse($tweet['legacy']['created_at'])->toDateTimeString(),
            'attachments'  => $attachments,
            'not_mentions' => true,
        ]);
        Queue::send(['id' => $tweet['rest_id']], Queue::TOPIC_STATUS_CREATE);
        $this->info("create Status : {$status['id']}");
    }


    public function handleMedia($medias, $tid)
    {
        $urls = [];
        $attachments = [];
        Attachment::where([
            ['tid', $tid],
            ['from_table', Status::class]
        ])->delete();

        foreach ($medias as $media) {
            if ($media['type'] == 'photo') {
                $urls[] = $media['url'];
                $client = $this->client->create([
                    'headers' => [
                        'User-Agent' => AttachmentServiceV2::getUa($media['media_url_https']),
                    ],
                ]);
                $response = $client->head($media['media_url_https']);
                $attachments[] = Attachment::create([
                    'tid'              => $tid,
                    'from_table'       => Status::class,
                    'url'              => $media['media_url_https'],
                    'remote_url'       => $media['media_url_https'],
                    'file_type'        => Attachment::FILE_TYPE_IMAGE,
                    'type'             => 'Document',
                    'media_type'       => $response->getHeaderLine('content-type'),
                    'thumbnail_height' => 0,
                    'thumbnail_width'  => 0,
                    'width'             => $media['original_info']['width'],
                    'height'            => $media['original_info']['height']
                ]);
            } else {
                $urls[] = $media['url'];

                // get max bitrate video
                $videos = $media['video_info']['variants'];
                $maxBitrate = -1;
                $url = "";
                foreach ($videos as $video) {
                    if ($video['content_type'] != 'video/mp4') {
                        continue;
                    }
                    if (isset($video['bitrate']) && $video['bitrate'] > $maxBitrate) {
                        $url = $video;
                    }
                }

                $attachments[] = Attachment::create([
                    'tid'              => $tid,
                    'from_table'       => Status::class,
                    'url'              => $url['url'],
                    'remote_url'       => $url['url'],
                    'thumbnail_url'    => $media['media_url_https'],
                    'file_type'        => Attachment::FILE_TYPE_VIDEO,
                    'type'             => 'Document',
                    'media_type'       => $url['content_type'],
                    'thumbnail_height' => 0,
                    'thumbnail_width'  => 0,
                    'width'            => $media['original_info']['width'],
                    'height'           => $media['original_info']['height']
                ]);
            }
        }
        return [$urls, $attachments];
    }
}
