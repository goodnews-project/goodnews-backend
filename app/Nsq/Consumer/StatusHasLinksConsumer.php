<?php

namespace App\Nsq\Consumer;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Model\Attachment;
use App\Model\PreviewCard;
use App\Model\PreviewCardsStatus;
use App\Nsq\Queue;
use App\Service\Activitypub\ActivitypubService;
use App\Service\AttachmentServiceV3;
use App\Service\OpenGraphService;
use App\Util\Log;
use App\Util\Media\Blurhash;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nsq\Result;

use function Co\go;
use function Hyperf\Support\env;

#[Consumer(topic: Queue::TOPIC_STATUS_HAS_LINKS, channel: 'event', name: 'status-links-create-preview', nums: 1)]
class StatusHasLinksConsumer extends BaseConsumer
{
    #[Inject]
    protected OpenGraphService $openGraphService;
    #[Inject]
    protected AttachmentServiceV3 $attachmentService;

    #[Inject]
    private ClientFactory $clientFactory;

    public function handle($message)
    {
        $urls = $message['urls'];

        foreach ($urls as $url) {

            $parsedUrl = parse_url($url);
            if (empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
                continue;
            }
            if ($previewCard = $this->generatePreviewCard($url)) {
                PreviewCardsStatus::create([
                    'status_id'       => $message['status_id'],
                    'preview_card_id' => $previewCard->id
                ]);
                break;
            }
        }
        return Result::ACK;
    }

    #[ExecTimeLogger('preview_card')]
    public function generatePreviewCard($url)
    {
        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent' => AttachmentServiceV3::getUa($url),
            ],
            'timeout' => 5,
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
        ]);
            $response = $client->get($url);
            var_dump("end {$url}");
        $opGraph = $this->openGraphService->parse($response->getBody()->getContents());

        if (empty($opGraph['title'])) {
            return false;
        }

        $metaData = [
            'title'         => $opGraph['title'] ?? null,
            'description'   => $opGraph['description'] ?? null,
            'provider_name' => $opGraph['site_name'] ?? null,
            'provider_url' => $opGraph['url'] ?? null,
        ];

        if (!empty($opGraph['image'])) {
            if ($download = $this->attachmentService->downloadAttachmentAndGetInfo($opGraph['image'])) {
                $metaData['image_url'] = $download['url'];
                $metaData['thumbnail_url'] = $download['thumbnail_url'];
                $metaData['blurhash'] = $download['blurhash'];
                Log::info('generatePreviewCard:image-download', ['image' => $opGraph['image'], 'download' => $download]);
            }
        }
        if ($metaData['provider_name'] == 'YouTube' && !empty($opGraph['video:url'])) {
            if (parse_url($opGraph['video:url'], PHP_URL_HOST) != 'www.youtube.com') {
                $metaData['provider_name'] = 'false-YouTube';
            }
            $url = $opGraph['video:url'];
        }
        if (empty($url)) {
            return null;
        }
        return PreviewCard::updateOrCreate(['url' => $url], $metaData);
    }

    private function getBlurhashInfoByImage($downloadUrl)
    {
        $imagePath = str_replace(env('ATTACHMENT_PRIEFIX'), BASE_PATH . '/public', $downloadUrl);
        if (!file_exists($imagePath)) {
            return [];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        return Blurhash::generate($imagePath, $mimeType);
    }
}
