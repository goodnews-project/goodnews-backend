<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\Nsqd\Api;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class NsqController extends AbstractController
{
    #[Inject]
    protected Api $api;

    #[OA\Get('/admin/_api/nsq/stats', summary: '查看统计信息', tags: ['admin', 'nsq'])]
    #[OA\Parameter(name: 'topic', description: '主题', in : 'query', required: false)]
    #[OA\Parameter(name: 'channel', description: '频道', in : 'query', required: false)]
    #[OA\Response(
        response: 200,
        description: '
        topic_name: 主题名称，表示当前主题的名称。
        channels: 通道数组，包含了当前主题下的所有通道的统计信息。
            channel_name: 通道名称，表示当前通道的名称。
            depth: 当前通道中的消息深度，即当前等待处理的消息数量。
            backend_depth: 后端深度，表示当前通道中的消息深度。
            in_flight_count: 当前正在处理的消息数量。
            deferred_count: 延迟处理的消息数量。
            message_count: 已处理的消息数量。
            requeue_count: 重新排队的消息数量。
            timeout_count: 超时的消息数量。
            client_count: 当前连接到该通道的客户端数量。
            clients: 连接到该通道的客户端列表。
            paused: 表示该通道是否被暂停。
            e2e_processing_latency: 端到端处理延迟统计信息，包括消息处理数量和百分位数。
                count: 消息处理数量。
                percentiles: 消息处理延迟的百分位数。
        depth: 主题的消息深度，即所有通道中等待处理的消息数量之和。
        backend_depth: 后端深度，表示主题中的消息深度。
        message_count: 主题中已处理的消息数量。
        message_bytes: 主题中的消息字节总数。
        paused: 表示主题是否被暂停。
        e2e_processing_latency: 主题的端到端处理延迟统计信息，包括消息处理数量和百分位数。
            count: 消息处理数量。
            percentiles: 消息处理延迟的百分位数。
        '
    )]
    public function stats()
    {
        $stats = $this->api->stats('json', $this->request->input('topic'), $this->request->input('channel'));
        return $this->response->json(json_decode($stats->getBody()->getContents(), true));
    }

    #[OA\Get('/admin/_api/nsq/ping', summary: '查看')]
    public function ping()
    {
        return ['health' => $this->api->ping()];
    }
}
