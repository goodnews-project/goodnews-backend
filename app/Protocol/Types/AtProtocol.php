<?php

namespace App\Protocol\Types;

class AtProtocol extends Protocol
{

    public function follow($actorId, $targetActorId)
    {
        // TODO: Implement follow() method.
    }

    public function unFollow($actorId, $targetActorId)
    {
        // TODO: Implement unFollow() method.
    }

    public function post($actorId, array $content)
    {
        // TODO: Implement post() method.
    }

    public function reBlog(string $actorId, array $content)
    {
        // TODO: Implement reBlog() method.
    }

    public function unReBlog(string $actorId, array $content)
    {
        // TODO: Implement unReBlog() method.
    }

    public function delete(string $actorId, array $content)
    {
        // TODO: Implement delete() method.
    }

    public function like(string $actorId, array $content)
    {
        // TODO: Implement like() method.
    }

    public function unLike(string $actorId, array $content)
    {
        // TODO: Implement unLike() method.
    }

    public function handleInbox(array $requestData)
    {
        // TODO: Implement handleInbox() method.
    }
}