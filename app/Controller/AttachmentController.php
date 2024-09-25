<?php

declare(strict_types=1);

namespace App\Controller;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Model\Attachment;
use App\Request\AttachmentRequest;
use App\Service\AttachmentServiceV3;
use App\Util\Media\Blurhash;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use League\Flysystem\Filesystem;
use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Support\env;
#[OA\HyperfServer('http')]
class AttachmentController extends AbstractController
{

    #[Inject]
    protected AttachmentServiceV3 $attachmentService;

    #[OA\Post(path:'/_api/v1/attachment',summary:'上传附件',tags:['附件'])]
    #[OA\Parameter(name: 'file', description: '文件', in : 'body', required: true)]
    #[OA\Response(
        response: 200,
        description: 'url 附件地址,id附件ID',
    )]
    #[ExecTimeLogger]
    public function upload(AttachmentRequest $request)
    {
        $file = $request->file('file');
        $ext = $file->getExtension();
        $attachment = $this->attachmentService->uploadAttachmentAndGetInfo($file->getRealPath(),$ext);
        $attachment = Attachment::create($attachment);
        return $this->response->json($attachment);
    }

    public function openUrl()
    {
        $url = $this->request->input('url');
        return $this->response->redirect($url)->withHeader('User-Agent', AttachmentServiceV3::getUa($url));
    }
    
    
}
