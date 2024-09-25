<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Attachment;
use App\Resource\Mastodon\AttachmentResource;
use App\Service\AttachmentService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(PassportAuthMiddleware::class)]
class MediaController extends AbstractController
{
    #[Inject]
    protected AttachmentService $attachmentService;

    #[OA\Put(path:'/api/v1/media/{id}',
        description: 'Update a MediaAttachment’s parameters, before it is attached to a status and posted.',
        summary:'', tags:['mastodon'])]
    public function update($id)
    {
        $attachment = Attachment::findOrFail($id);
        $attachment->focus = $this->request->input('focus');
        $attachment->name = $this->request->input('description');
        $attachment->save();
        return AttachmentResource::make($attachment);
    }

    #[OA\Get(path:'/api/v1/media/{id}',
        description: 'Get a media attachment, before it is attached to a status and posted, but after it is accepted for processing.',
        summary:'https://docs.joinmastodon.org/methods/media/#get', tags:['mastodon'])]
    public function get($id)
    {
        $attachment = Attachment::findOrFail($id);
        return AttachmentResource::make($attachment);
    }

}
