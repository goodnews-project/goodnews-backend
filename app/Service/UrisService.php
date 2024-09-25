<?php

declare(strict_types=1);

namespace App\Service;

use function Hyperf\Support\env;

class UrisService
{
    const UsersPath = 'users';
    const StatusesPath = 'statuses';
    const InboxPath = 'inbox';
    const OutboxPath = 'outbox';
    const FollowersPath = 'followers';
    const FollowingPath = 'following';
    const LikedPath = 'liked';
    const CollectionsPath = 'collections';
    const FeaturedPath = 'featured';
    const PublicKeyPath = 'main-key';
    const FollowPath = 'follow';
    const UpdatePath = 'updates';
    const RepliesPath = 'replies';

    const UsersUri = '{username}';
    const StatusesUri = self::UsersUri . '/' . self::StatusesPath . '/{statusId}';
    const RepliesUri = self::StatusesUri . '/'. self::RepliesPath;
    const InboxUri = self::UsersUri . '/' . self::InboxPath;
    const OutboxUri = self::UsersUri . '/' . self::OutboxPath;
    const FollowersUri = self::UsersUri . '/' . self::FollowersPath;
    const FollowingUri = self::UsersUri . '/' . self::FollowingPath;
    const LikedUri = self::UsersUri . '/' . self::LikedPath;
    const PublicKeyUri = self::UsersUri . '/' . self::PublicKeyPath;

    public static function generateURIsForAccount(string $username)
    {
        $hostURL = getApHostUrl();

        // The below URLs are used for serving web requests
        $userURL = sprintf('%s/user/%s', $hostURL, $username);
        $statusesURL = sprintf('%s/%s', $userURL, self::StatusesPath);

        // the below URIs are used in ActivityPub and Webfinger
        $userURI = sprintf('%s/users/%s', $hostURL, $username);
        $statusesURI = sprintf('%s/%s', $userURI, self::StatusesPath);
        $inboxURI = sprintf('%s/%s', $userURI, self::InboxPath);
        $outboxURI = sprintf('%s/%s', $userURI, self::OutboxPath);
        $followersURI = sprintf('%s/%s', $userURI, self::FollowersPath);
        $followingURI = sprintf('%s/%s', $userURI, self::FollowingPath);
        $likedURI = sprintf('%s/%s', $userURI, self::LikedPath);
        $publicKeyURI = sprintf('%s#%s', $userURI, self::PublicKeyPath);
        $shareInboxUri = $hostURL.'/inbox';

        return compact('hostURL', 'userURL', 'statusesURL', 'userURI', 'statusesURI', 'inboxURI', 'shareInboxUri',
            'outboxURI', 'followersURI', 'followingURI', 'likedURI', 'publicKeyURI');
    }

}