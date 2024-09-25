<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V2;

use App\Controller\AbstractController;
use App\Model\Attachment;
use App\Request\Mastodon\MediaRequest;
use App\Resource\Mastodon\AttachmentResource;
use App\Service\AttachmentService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(PassportAuthMiddleware::class)]
class MediaController extends AbstractController
{
    #[Inject]
    protected AttachmentService $attachmentService;

    #[OA\Post(path:'/api/v2/media',
        description: 'Creates a media attachment to be used with a new status. The full sized media will be processed asynchronously in the background for large uploads.',
        summary:'https://docs.joinmastodon.org/methods/media/#v2', tags:['mastodon'])]
    public function upload(MediaRequest $mediaRequest)
    {
        $payload = $mediaRequest->validated();
        $file = $payload['file'];
        $type = 'image';
        if($file->getExtension() == 'mp4'){
            $type = 'video';
        }
        $attachment = $this->attachmentService->upload($payload['file'], $type);
        if (!empty($payload['thumbnail']) && $payload['thumbnail'] instanceof UploadedFile) {
            $this->attachmentService->upload($payload['thumbnail'], 'image');
        }

        return AttachmentResource::make($attachment);
    }

    #[OA\Put(path:'/api/v2/media/{id}',
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

    #[OA\Get(path:'/api/v2/media/{id}',
        description: 'Get a media attachment, before it is attached to a status and posted, but after it is accepted for processing.',
        summary:'', tags:['mastodon'])]
    public function get($id)
    {
        $attachment = Attachment::findOrFail($id);
        return AttachmentResource::make($attachment);
    }

}
