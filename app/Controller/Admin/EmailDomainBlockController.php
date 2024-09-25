<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\EmailDomainBlock;
use App\Request\Admin\EmailDomainBlockRequest;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class EmailDomainBlockController extends AbstractController
{
    #[OA\Get('/_api/admin/email_domain_blocks', summary:"电子邮件域名屏蔽列表", tags:["admin", "email_domain_blocks"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        $emailDomainBlocks = EmailDomainBlock::latest()->paginate(20);
        return $emailDomainBlocks;
    }

    #[OA\Post('/_api/admin/email_domain_blocks', summary:"新增电子邮件域名屏蔽", tags:["admin", "email_domain_blocks"])]
    #[OA\Parameter(name: 'domain', description: '域名', in : 'body')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function store(EmailDomainBlockRequest $request)
    {
       $payload = $request->validated() ;
       EmailDomainBlock::create($payload);
       return $this->response->raw(null)->withStatus(201);
    }

    #[OA\Delete('/_api/admin/email_domain_blocks/{id}', summary:"删除电子邮件域名屏蔽", tags:["admin", "email_domain_blocks"])] 
    #[OA\Parameter(name: 'id', description: '域名', in : 'path')] 
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function destory($id)
    {
        $ids = explode(',',$id);
        EmailDomainBlock::destroy($id);
        return $this->response->raw(null)->withStatus(201); 
    }

}
