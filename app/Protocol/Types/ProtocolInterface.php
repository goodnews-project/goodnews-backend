<?php

namespace App\Protocol\Types;

interface ProtocolInterface
{
    /**
     * 关注
     * @return mixed
     */
    public function follow($actorId, $targetActorId);

    /**
     * 取消关注
     * @return mixed
     */
    public function unFollow($actorId, $targetActorId);

    /**
     * 发帖
     * @return mixed
     */
    public function post($actorId, array $content);

    /**
     * 转帖
     * @return mixed
     */
    public function reBlog(string $actorId, array $content);

    /**
     * 取消转帖
     * @return mixed
     */
    public function unReBlog(string $actorId, array $content);

    /**
     * 删帖
     * @return mixed
     */
    public function delete(string $actorId, array $content);

    /**
     * 点赞
     * @return mixed
     */
    public function like(string $actorId, array $content);

    /**
     * 取消点赞
     * @return mixed
     */
    public function unLike(string $actorId, array $content);

    /**
     * 处理收件箱
     * @return mixed
     */
    public function handleInbox(array $requestData);

}