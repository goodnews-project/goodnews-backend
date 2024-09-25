<?php

namespace App\Service;

use App\Model\Account;
use App\Model\Hashtag;
use App\Model\Status;
use App\Model\StatusHashtag;
use App\Resource\StatusPaginateResource;
use App\Service\Activitypub\ActivitypubService;
use App\Util\ActivityPub\Helper;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Redis\Redis;
use Hyperf\Stringable\Str;
use function Hyperf\Support\make;

class SearchService
{
    const S_TAG_KEY = 's:hashtag:%s';
    public static function query($data)
    {
        return (new self)->run($data);
    }

    protected function run($data)
    {
        $q = urldecode($data['q']);
        $resolve = $data['resolve'] ?? null;
        $type = $data['type'] ?? null;

        if($resolve &&
            ( Str::startsWith($q, 'https://') ||
              Str::substrCount($q, '@') >= 1)
        ) {
            $res = $this->resolveQuery($data);
            if (empty($res)) {
                return  $this->accounts($data);
            }
            return $res;
        }

        if($type) {
            switch ($type) {
                case 'accounts':
                    return  $this->accounts($data);
                case 'statuses':
                    return  $this->statuses($data);
                case 'hashtags':
                    return $this->hashtags($data);
            }
        }

        return make(LengthAwarePaginatorInterface::class, ['items' => \Hyperf\Collection\collect(), 'total' => 0, 'perPage' => 1]);
    }

    public static function exploreStatusHashtags($tag)
    {
        $tag = urldecode($tag);
        $hashtag = Hashtag::where('name', $tag)->first();
        if (empty($hashtag)) {
            return make(LengthAwarePaginatorInterface::class, ['items' => \Hyperf\Collection\collect(), 'total' => 0, 'perPage' => 1]);
        }
        $status = Status::withInfo(Auth::account())
            ->rightJoin('status_hashtag as sh', 'status.id', '=', 'sh.status_id')
            ->where('sh.hashtag_id', $hashtag->id);
        $statusList = $status->select('status.*')->where('status.scope', Status::SCOPE_PUBLIC)
            ->orderByDesc('status.id')->paginate(30);
        return StatusPaginateResource::make($statusList);
    }

    protected function accounts($data)
    {
        $rawQuery = $data['q'] ?? '';
        // todo 暂时不用es
//        $es = Account::getEs();
//        $res = $es->searchDocument(['query' => ['wildcard' => ['username' => ['value' => '*'.$rawQuery.'*']]]]);
//        $data = $es->getResponseSource($res);
        // ->whereIn('id', \Hyperf\Collection\collect($data)->pluck('id')->toArray())
        return Account::isFollow(Auth::account())
            ->where('acct', 'like', '%'.$rawQuery.'%')
            ->orWhere('display_name', 'like', '%'.$rawQuery.'%')
            ->orderByDesc('followers_count')
            ->paginate(30);
    }

    protected function statuses($data)
    {
        $rawQuery = $data['q'] ?? '';
        // todo 暂时不用es
//        $es = Status::getEs();
//        $res = $es->searchDocument(['query' => ['wildcard' => ['content' => ['value' => '*'.$rawQuery.'*']]]]);
//        $data = $es->getResponseSource($res);
        // whereIn('id', \Hyperf\Collection\collect($data)->pluck('id')->toArray())
        $statusList = Status::withInfo(Auth::account())->where('content','like','%'.$rawQuery.'%')
            ->orderByDesc('fave_count')
            ->paginate(30);
        return StatusPaginateResource::make($statusList);
    }

    protected function hashtags($data)
    {
        $q = $data['q'] ?? '';
        $limit = 30;
        $getTagIds = $data['getTagIds'] ?? null;
        $query = str_starts_with($q, '#') ? substr($q,1) : $q;
        // todo 暂时不用es
//        $es = Hashtag::getEs();
//        $res = $es->searchDocument(['query' => ['wildcard' => ['name' => ['value' => '*'.$query.'*']]]]);
//        $r = $es->getResponseSource($res);
//        $tagIds = \Hyperf\Collection\collect($r)->pluck('id');
//        if ($getTagIds) {
//            return $tagIds;
//        }
//        var_dump(1212121);
        $hashtagPages = Hashtag::where('name', 'like', '%'.$query.'%')->latest('id')->paginate($limit);
        foreach ($hashtagPages as $hashtagPage) {
            $tagStatInfo = self::getStatInfoByHashtagId($hashtagPage->id);
            foreach ($tagStatInfo as $key => $value) {
                $hashtagPage->{$key} = $value;
            }
        }
        return $hashtagPages;
    }

    protected function resolveQuery($data)
    {
        $query = urldecode($data['q']);
        if(str_starts_with($query, '@') && !str_contains($query, '.')) {
            return $this->accounts(['q' => substr($query, 1)]);
        }

        if(!Helper::validateUrl($query) && strpos($query, '@') == -1) {
            return [];
        }

        if(!str_starts_with($query, 'http') && Str::substrCount($query, '@') == 1 && !str_starts_with($query, '@')) {
            try {
                $res = WebfingerService::lookup('@' . $query);
            } catch (\Exception $e) {
                return [];
            }
            if($res && isset($res['id'])) {
                return $this->accounts(['q' => $res['acct']]);
            } else {
                return [];
            }
        }

        if(Str::substrCount($query, '@') == 2) {
            try {
                $res = WebfingerService::lookup($query);
            } catch (\Exception $e) {
                return [];
            }
            if($res && isset($res['id'])) {
                return $this->accounts(['q' => $res['acct']]);
            } else {
                return [];
            }
        }

        try {
            $res = ActivityPubService::get($query);
            if($res) {
                $json = json_decode($res, true);

                if(!$json || !isset($json['@context']) || !isset($json['type']) || !in_array($json['type'], ['Note', 'Person'])) {
                    return [];
                }

                switch($json['type']) {
                    case 'Note':
                        $obj = Helper::statusFirstOrFetch($query);
                        if(!$obj || !isset($obj['id'])) {
                            return [];
                        }

                        return $this->statuses($data);

                    case 'Person':
                        $obj = Helper::accountFetch($query);
                        if(!$obj) {
                            return [];
                        }

                        return $this->accounts(['q' => $obj['acct']]);

                    default:
                        return [];
                }
            }
        } catch (\Exception $e) {
            Log::error('exception: ' . $e->getMessage());
            return [];
        }

        return [];
    }

    public static function getStatInfoByHashtagId($hashtagId)
    {
        $redis = make(Redis::class);
        $key = sprintf(self::S_TAG_KEY, $hashtagId);
        if ($statInfo = $redis->get($key)) {
            return json_decode($statInfo, true);
        }
        $statusIds = StatusHashtag::where('hashtag_id', $hashtagId)->pluck('status_id')->toArray();
        $status = Status::whereIn('id', $statusIds);

        // 讨论人数
        $discussCount = $status->selectRaw('count(distinct account_id) as cnt')->value('cnt');

        // 推文总数
        $statusTotal = $status->count();

        // 今日推文数
        $statusTodayTotal = $status->where('created_at', '>', Carbon::today())->count();
        $data = compact('discussCount', 'statusTotal', 'statusTodayTotal');
        $redis->setEx($key, 300, json_encode($data));
        return $data;
    }
}
