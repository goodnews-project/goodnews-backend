<?php

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Notification;
use App\Resource\Mastodon\NotificationResource;
use App\Service\Auth;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class NotificationController extends AbstractController
{
    #[OA\Get(path:'/api/v1/notifications',
    description: 'Notifications concerning the user',
    summary:'https://docs.joinmastodon.org/methods/notifications/#get', tags:['mastodon'])]
    public function index()
    {
        $types = $this->request->input('types');
        $excludeTypes = $this->request->input('exclude_types');
        $account = Auth::passport();
        $notifications = Notification::where('target_account_id',$account['id']);
        if($types){
            $typeIn = [];
            foreach($types as $type){
                $typeIn[] = array_search($type,NotificationResource::TYPE_MAP);
            }
            $notifications->whereIn('notify_type',$typeIn);
        }
        if($excludeTypes){
            $typeNotIn =[];
            foreach($excludeTypes as $excludeType){
                $typeNotIn[] = array_search($type,NotificationResource::TYPE_MAP);
            }
            $notifications->whereNotIn('notify_type',$typeNotIn);
        } 
        $notifications = $notifications->latest()->paginate();
        return NotificationResource::collection($notifications)->toArray();
    }
}
