<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for statusReblogged
 * title StatusReblogged represents a reblogged status.
 */
class StatusRebloggedModel extends MastodonModel
{
	public AccountModel $account;
	public ApplicationModel $application;

	/** Description: This status has been bookmarked by the account viewing it. */
	public bool $bookmarked;
	public CardModel $card;

	/**
	 * Example: <p>Hey this is a status!</p>
	 * Description: The content of this status. Should be HTML, but might also be plaintext in some cases.
	 */
	public string $content;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: The date when this status was created (ISO 8601 Datetime).
	 */
	public string $created_at;

	/** Description: Custom emoji to be used when rendering status content. */
	public array $emojis;

	/** Description: This status has been favourited by the account viewing it. */
	public bool $favourited;

	/** Description: Number of favourites/likes this status has received, according to our instance. */
	public int $favourites_count;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: ID of the status.
	 */
	public string $id;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: ID of the account being replied to.
	 */
	public string $in_reply_to_account_id;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: ID of the status being replied to.
	 */
	public string $in_reply_to_id;

	/**
	 * Example: en
	 * Description: Primary language of this status (ISO 639 Part 1 two-letter language code).
	 * Will be null if language is not known.
	 */
	public string $language;

	/** Description: Media that is attached to this status. */
	public array $media_attachments;

	/** Description: Mentions of users within the status content. */
	public array $mentions;

	/** Description: Replies to this status have been muted by the account viewing it. */
	public bool $muted;

	/** Description: This status has been pinned by the account viewing it (only relevant for your own statuses). */
	public bool $pinned;
	public PollModel $poll;
	public StatusRebloggedModel $reblog;

	/** Description: This status has been boosted/reblogged by the account viewing it. */
	public bool $reblogged;

	/** Description: Number of times this status has been boosted/reblogged, according to our instance. */
	public int $reblogs_count;

	/** Description: Number of replies to this status, according to our instance. */
	public int $replies_count;

	/** Description: Status contains sensitive content. */
	public bool $sensitive;

	/**
	 * Example: warning nsfw
	 * Description: Subject, summary, or content warning for the status.
	 */
	public string $spoiler_text;

	/** Description: Hashtags used within the status content. */
	public array $tags;

	/**
	 * Description: Plain-text source of a status. Returned instead of content when status is deleted,
	 * so the user may redraft from the source text without the client having to reverse-engineer
	 * the original text from the HTML content.
	 */
	public string $text;

	/**
	 * Example: https://example.org/users/some_user/statuses/01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: ActivityPub URI of the status. Equivalent to the status's activitypub ID.
	 */
	public string $uri;

	/**
	 * Example: https://example.org/@some_user/statuses/01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: The status's publicly available web URL. This link will only work if the visibility of the status is 'public'.
	 */
	public string $url;

	/**
	 * Example: unlisted
	 * Description: Visibility of this status.
	 */
	public string $visibility;
}
