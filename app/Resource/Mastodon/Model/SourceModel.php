<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for Source
 * title Source represents display or publishing preferences of user's own account.
 */
class SourceModel extends MastodonModel
{
	/**
	 * Description: This account is aliased to / also known as accounts at the
	 * given ActivityPub URIs. To set this, use `/api/v1/accounts/alias`.
	 *
	 * Omitted from json if empty / not set.
	 */
	public array $also_known_as_uris;

	/** Description: Metadata about the account. */
	public array $fields;

	/** Description: The number of pending follow requests. */
	public int $follow_requests_count;

	/** Description: The default posting language for new statuses. */
	public string $language;

	/** Description: Profile bio. */
	public string $note;

	/**
	 * Description: The default post privacy to be used for new statuses.
	 * public = Public post
	 * unlisted = Unlisted post
	 * private = Followers-only post
	 * direct = Direct post
	 */
	public string $privacy;

	/** Description: Whether new statuses should be marked sensitive by default. */
	public bool $sensitive;

	/** Description: The default posting content type for new statuses. */
	public string $status_content_type;
}
