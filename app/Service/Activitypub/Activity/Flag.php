<?php

namespace App\Service\Activitypub\Activity;

use App\Exception\InboxException;
use App\Model\Account;
use App\Model\Instance;
use App\Model\Report;
use App\Service\UrisService;
use App\Util\ActivityPub\Helper;
use Hyperf\Collection\Arr;

class Flag extends Activity
{
    public function validate()
    {
        $object = $this->object;

        if (empty($object) || (!is_array($object) && !is_string($object))) {
            throw new InboxException('invalid object');
        }

        if (is_array($object) && count($object) > 100) {
            throw new InboxException('count of object must be less than 100');
        }

    }

    public function store()
    {

        $id = $this->id;
        $actor = $this->actor;

        $content = null;
        if (isset($this->payload['content'])) {
            if (strlen($this->payload['content']) > 5000) {
                $content = handleStatusContent(substr($this->payload['content'], 0, 5000) . ' ... (truncated message due to exceeding max length)');
            } else {
                $content = handleStatusContent($this->payload['content']);
            }
        }

        $objects = \Hyperf\Collection\collect([]);
        $targetAccountId = null;

        $object = $this->object;
        foreach ($object as $objectUrl) {
            if (!Helper::validateLocalUrl($objectUrl)) {
                continue;
            }

            if (str_contains($objectUrl, '/' . UrisService::UsersPath . '/')) {
                $username = Arr::last((explode('/', $objectUrl)));
                $account = Account::where('acct', $username)->first();
                $targetAccountId = $account?->id;
            } else if (str_contains($objectUrl, '/' . UrisService::StatusesPath . '/')) {
                $statusId = Arr::last(explode('/', $objectUrl));
                $objects->push($statusId);
            }
        }

        if (!$targetAccountId && !$objects->count()) {
            return;
        }

        $this->inboxService->createReport(
            $targetAccountId, $objects->toArray(), $content, $actor, $object, $id
        );
    }
}