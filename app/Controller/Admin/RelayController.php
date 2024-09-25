<?php

declare(strict_types=1);

namespace App\Controller\Admin;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Model\Relay;
use App\Nsq\NsqQueueMessage;
use App\Request\RelayRequest;
use App\Service\AccountService;
use App\Util\ActivityPub\Helper;
use Hyperf\Swagger\Annotation as OA;
use App\Controller\AbstractController;
use function Hyperf\Support\env;

#[OA\HyperfServer('http')]
class RelayController extends AbstractController
{

    #[OA\Get('/_api/admin/relays', summary:"中继站列表", tags:["admin", "relay"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        return Relay::latest('id')->get();
    }

    #[OA\Put('/_api/admin/relays/{id}/disable', summary:"禁用", tags:["admin", "relay"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function disable($id)
    {
        $relay = Relay::findOrFail($id);
        $relay->state = Relay::STATE_IDLE;
        $relay->save();
        $this->unfollow_activity($id);
        return $this->response->raw(null);
    }

    #[OA\Put('/_api/admin/relays/{id}/enable', summary:"启用", tags:["admin", "relay"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function enable($id)
    {
        $relay = Relay::findOrFail($id);
        $relay->state = Relay::STATE_PENDING;
        $relay->follow_activity_id = $this->generate_activity_id();
        $relay->save();
        $this->follow_activity($id);
        return $this->response->raw(null);
    }

    #[OA\Delete('/_api/admin/relays/{id}', summary:"删除", tags:["admin", "relay"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function delete($id)
    {
        $relay = Relay::findOrFail($id);
        $relay->delete();
        $this->unfollow_activity($id);
        return $this->response->raw(null);
    }

    #[OA\Post('/_api/admin/relays', summary:"添加并启用", tags:["admin", "relay"])]
    #[OA\Parameter(name: 'inbox_url', description: 'inbox url', in : 'query')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function create(RelayRequest $request)
    {
        $payload = $request->validated();
        $inbox_url = $payload['inbox_url'];

        $replay = Relay::create([
            'inbox_url' => $inbox_url,
            'follow_activity_id' => $this->generate_activity_id(),
            'state' => Relay::STATE_PENDING,
        ]);
        $this->follow_activity($replay->id);
        return $replay;
    }

    #[NsqQueueMessage]
    public function follow_activity($id)
    {
        $replay = Relay::withTrashed()->findOrFail($id);
        $account = AccountService::getActor();
        $apData = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $replay->follow_activity_id,
            'type'     => ActivityPubActivityInterface::TYPE_FOLLOW,
            'actor'    => $account->permalink(),
            'object'   => ActivityPubActivityInterface::PUBLIC_URL
        ];

        Helper::sendSignedObject($account, $replay->inbox_url, $apData);
    }

    #[NsqQueueMessage]
    public function unfollow_activity($id)
    {
        $replay = Relay::withTrashed()->findOrFail($id);
        $account = AccountService::getActor();
        $actor = $account->permalink();
        $apData = [
            '@context' => ActivityPubActivityInterface::CONTEXT_URL,
            'id'       => $this->generate_activity_id(),
            'type'     => ActivityPubActivityInterface::TYPE_UNDO,
            'actor'    => $actor,
            'object'   => [
                'id' => $replay->follow_activity_id,
                'type' => ActivityPubActivityInterface::TYPE_FOLLOW,
                'actor' => $actor,
                'object' => ActivityPubActivityInterface::PUBLIC_URL,
            ]
        ];
        Helper::sendSignedObject($account, $replay->inbox_url, $apData);
    }

    private function generate_activity_id(): string
    {
        return getApHostUrl().'/'.uniqid('aid-'.time().'-');
    }
}
