<?php

namespace App\Entity\Contracts;

interface ActivityPubActivityInterface
{
    public const FOLLOWERS = 'followers';
    public const FOLLOWING = 'following';
    public const INBOX = 'inbox';
    public const OUTBOX = 'outbox';
    public const CONTEXT = 'context';
    public const CONTEXT_URL = 'https://www.w3.org/ns/activitystreams';
    public const SECURITY_URL = 'https://w3id.org/security/v1';
    public const PUBLIC_URL = 'https://www.w3.org/ns/activitystreams#Public';

    public const TYPE_NOTE = 'Note';
    public const TYPE_DELETE = 'Delete';
    public const TYPE_FOLLOW = 'Follow';
    public const TYPE_UNDO = 'Undo';
    public const TYPE_LIKE = 'Like';
    public const TYPE_CREATE = 'Create';
    public const TYPE_UPDATE = 'Update';
    public const TYPE_COLLECTION = 'Collection';
    public const TYPE_ORDERED_COLLECTION = 'OrderedCollection';
    public const TYPE_COLLECTION_PAGE = 'CollectionPage';
    public const TYPE_TOMBSTONE = 'Tombstone';
    public const TYPE_QUESTION = 'Question';
    public const TYPE_MENTION = 'Mention';
    public const TYPE_HASHTAG = 'Hashtag';
    public const TYPE_EMOJI = 'Emoji';
    public const TYPE_ANNOUNCE = 'Announce';
    public const TYPE_ACCEPT = 'Accept';
    public const TYPE_REJECT = 'Reject';
    public const TYPE_PERSON = 'Person';
    public const TYPE_IMAGE = 'Image';
    public const TYPE_Flag = 'Flag';
}
