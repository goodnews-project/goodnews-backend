<?php

namespace App\Service;

use App\Exception\AppException;
use App\Model\Account;
use App\Model\Block;
use App\Model\Follow;

use App\Model\FollowRecommendation;
use App\Model\FollowRequest;
use App\Model\Mute;
use App\Model\Notification;
use App\Model\Report;
use App\Model\User;
use App\Nsq\Queue;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use Carbon\Carbon;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use function Hyperf\Config\config;
use function Hyperf\Support\make;
use function Hyperf\Translation\trans;

class AccountService
{
    #[Inject]
    protected Redis $redis;

    public static function getActor()
    {
        $account = Account::where('acct', 'actor')->first();
        if (empty($account)) {
            return (new static())->create([
                'username' => 'actor',
                'display_name' => 'good-news',
                'acct' => 'actor',
                'note' => 'internal service actor',
                'manually_approves_follower' => 1,
                'actor_type' => Account::ACTOR_TYPE_SERVICE,
                'is_activate' => 1
            ]);
        }
        return $account;
    }

    public function details($acct, $account = null)
    {
        $query = Account::where('acct', $acct)
            ->withCount(['tweets'])
            ->with('user:id,account_id,role_id');

        if ($account) {
            $query->with(['follower' => fn($q) => $q->where('account_id', $account['id'])]);
            $query->with(['subscribed' => fn($q) => $q->where('account_id', $account['id'])->where('expired_at', '>', Carbon::now())]);
        }

        $account = $query->firstOrFail();

        return compact('account');
    }

    public function afterVerifyCredentials(User $user)
    {
        $user->update([
            'current_signin_at' => Carbon::now(),
            'current_signin_ip' => getClientIp()
        ]);
        $expire = config('auth.guards.jwt.ttl');
        make(UserService::class)->setActive($user->id);
        $this->redis->setEx(sprintf(UserService::S_ACCOUNT_TOKEN_KEY, $user->account->id), $expire, null);
    }

    public function follow($accountId, $authAccountId)
    {
        if ($accountId == $authAccountId) {
            throw new AppException('follow_error.cant_follow_self', 403);
        }

        $target = Account::findOrFail($accountId);
        if (in_array($authAccountId, $target->blocks->pluck('target_account_id')->toArray())) {
            throw new AppException('follow_error.blocked', 403);
        }

        $data['account_id'] = $authAccountId;
        $data['target_account_id'] = $target->id;
        $data['action'] = Follow::ACTION_FOLLOW;

        if ($target->manually_approves_follower) {
            FollowRequest::firstOrCreate([
                'account_id'        => $authAccountId,
                'target_account_id' => $accountId
            ]);

            if ($target->isLocal()) {
                Notification::create([
                    'account_id' => $authAccountId,
                    'target_account_id' => $accountId,
                    'notify_type' => Notification::NOTIFY_TYPE_FOLLOW_REQUEST,
                ]);
            }
        } else {
            $follow = Follow::firstOrCreate([
                'account_id'        => $authAccountId,
                'target_account_id' => $accountId
            ]);
            Queue::send($follow->toArray(), Queue::TOPIC_FOLLOW);
        }
        Queue::send($data, Queue::TOPIC_FOLLOW_AP);
    }

    public function unFollow($accountId, $authAccountId)
    {
        $follow = Follow::where([
            'account_id'        => $authAccountId,
            'target_account_id' => $accountId
        ])->first();
        if (!$follow) {
            $followRequest = FollowRequest::where([
                'account_id'        => $authAccountId,
                'target_account_id' => $accountId
            ])->first();
            if ($followRequest) {
                $followRequest->delete();
            }
            return;
        }

        $follow->delete();
        $data = $follow->toArray();
        $data['action'] = Follow::ACTION_UNFOLLOW;
        Queue::send($data, Queue::TOPIC_FOLLOW_AP);
    }

    public function mute($loginAccountId, $targetAccountId, $duration = 0, $notifications = true)
    {
        $expiresAt = null;
        if ($duration > 0) {
            Carbon::now()->addSeconds($duration);
        }

        Mute::updateOrCreate([
            'account_id'        => $loginAccountId,
            'target_account_id' => $targetAccountId,
        ], ['expires_at' => $expiresAt]);
    }

    public function unmute($loginAccountId, $targetAccountId)
    {
        Mute::where([
            ['account_id', $loginAccountId],
            ['target_account_id', $targetAccountId]
        ])->delete();
    }

    public function block($loginAccountId, $targetAccountId)
    {
        Block::updateOrCreate([
            'account_id'        => $loginAccountId,
            'target_account_id' => $targetAccountId
        ]);
    }

    public function unblock($loginAccountId, $targetAccountId)
    {
        Block::where([
            ['account_id', $loginAccountId],
            ['target_account_id', $targetAccountId]
        ])->delete();
    }

    public function getSuggestions(Account $authAccount, $limit = 40)
    {
        // todo 无个性化推荐之前，使用系统推荐数据
        $accountIds = FollowRecommendation::where('status', FollowRecommendation::STATUS_UNSUPPRESSED)
            ->where('id', '<>', $authAccount->id)
            ->where('language', $authAccount->language)
            ->whereNotIn('account_id', $authAccount->follows->pluck('target_account_id'))
            ->latest('rank')
            ->limit($limit)
            ->pluck('account_id');
        return Account::findMany($accountIds);
    }

    public function create($data)
    {
        $pkiConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $pki = openssl_pkey_new($pkiConfig);
        openssl_pkey_export($pki, $pkiPrivate);
        $pkiPublic = openssl_pkey_get_details($pki);
        return Account::create(array_merge([
            'is_activate'  => false,
            'private_key'  => $pkiPrivate,
            'public_key'   => $pkiPublic['key'],
        ], $data));
    }

    public function followRequests(Account $account, $limit = 40)
    {
        $followerIds = FollowRequest::where('target_account_id', $account->id)->pluck('account_id');
        return Account::whereIn('id', $followerIds)->limit($limit)->get();
    }

    public function followRequestAccept(Account $account, $id)
    {
        $accountId = $account->id;
        $target = Account::find($id);
        if (!$target) {
            throw new AppException('record_not_found');
        }

        $followRequest = FollowRequest::where('target_account_id', $accountId)->where('account_id', $id)->first();
        if (!$followRequest) {
            throw new AppException('record_not_found');
        }

        Follow::firstOrCreate(['account_id' => $id, 'target_account_id' => $accountId]);

        $account->followers_count++;
        $account->save();

        $target->following_count++;
        $target->save();

        if ($target->isRemote()) {
            Queue::send($followRequest->toArray(), Queue::TOPIC_FOLLOW_ACCEPT);
        } else {
            Notification::create([
                'account_id' => $id,
                'target_account_id' => $accountId,
                'notify_type' => Notification::NOTIFY_TYPE_FOLLOW,
            ]);
            $followRequest->delete();
        }
    }

    public function followRequestReject(Account $account, $id)
    {
        $accountId = $account->id;
        $target = Account::find($id);
        if (!$target) {
            throw new AppException('record_not_found');
        }

        $followRequest = FollowRequest::where('target_account_id', $accountId)->where('account_id', $id)->first();
        if (!$followRequest) {
            throw new AppException('record_not_found');
        }

        if ($target->isRemote()) {
            Queue::send($followRequest->toArray(), Queue::TOPIC_FOLLOW_REJECT);
        } else {
            $followRequest->delete();
        }
    }

    public function getByAcct($acct)
    {
        return Account::where('acct', $acct)->firstOrFail();
    }

    public function report($payload, $authAccountId)
    {
        return Report::create([
            'account_id' => $authAccountId,
            'target_account_id' => $payload['account_id'],
            'forward' => $payload['forward'] ?? false,
            'forward_to_domains' => $payload['forward_to_domains'] ?? null,
            'status_ids' => $payload['status_ids'] ?? null,
            'rule_ids' => $payload['rule_ids'] ?? null,
            'comment' => $payload['comment'] ?? null,
        ]);
    }

    public function getRenderedNote($note)
    {
        $parseNote = Extractor::create($note)->extract();
        $mentions = [];
        foreach ($parseNote['mentions'] as $mention) {
            $account = WebfingerService::lookup($mention);
            if (empty($account)) {
                continue;
            }
            $mentions[] = ['username' => $account['username'], 'acct' => $account['acct']];
        }

        if (empty($mentions)) {
            return $note;
        }

        return Autolink::create($note)
            ->setTarget('')
            ->setMentions($mentions)
            ->setAutolinkActiveUsersOnly(true)
            ->setBaseHashPath('/explore/hashtag/')
            ->autoLink();
    }

    public function getDefaultSubscriberPlan($fee, $isLongTerm = 1)
    {
        $calculateFee = intval(substr($fee, 0, -18));
        $calculatePlanFeeFn = function ($term, $discount) use ($calculateFee) {
            return (string) floor($calculateFee * $term * $discount);
        };

        if ($isLongTerm == 0) {
            return [
                ['id' => 1, 'plan_discount' => 1, 'plan_fee' => str_pad($plan_fee1 = $calculatePlanFeeFn(1, 1), 18 + strlen($plan_fee1), '0'), 'plan_term' => 1],
            ];
        }

        return [
            ['id' => 1, 'plan_discount' => 1, 'plan_fee' => str_pad($plan_fee1 = $calculatePlanFeeFn(1, 1), 18 + strlen($plan_fee1), '0'), 'plan_term' => 1],
            ['id' => 2, 'plan_discount' => 0.9, 'plan_fee' => str_pad($plan_fee2 = $calculatePlanFeeFn(3, 0.9), 18 + strlen($plan_fee2), '0'), 'plan_term' => 3],
            ['id' => 3, 'plan_discount' => 0.8, 'plan_fee' => str_pad($plan_fee3 = $calculatePlanFeeFn(6, 0.8), 18 + strlen($plan_fee3), '0'), 'plan_term' => 6],
            ['id' => 4, 'plan_discount' => 0.7, 'plan_fee' => str_pad($plan_fee4 = $calculatePlanFeeFn(12, 0.7), 18 + strlen($plan_fee4), '0'), 'plan_term' => 12],
        ];
    }
}
