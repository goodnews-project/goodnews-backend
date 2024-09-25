<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for poll
 * title Poll represents a poll attached to a status.
 */
class PollModel extends MastodonModel
{
	/** Description: Custom emoji to be used for rendering poll options. */
	public array $emojis;

	/** Description: Is the poll currently expired? */
	public bool $expired;

	/** Description: When the poll ends. (ISO 8601 Datetime). */
	public string $expires_at;

	/**
	 * Example: 01FBYKMD1KBMJ0W6JF1YZ3VY5D
	 * Description: The ID of the poll in the database.
	 */
	public string $id;

	/** Description: Does the poll allow multiple-choice answers? */
	public bool $multiple;

	/** Description: Possible answers for the poll. */
	public array $options;

	/**
	 * Description: When called with a user token, which options has the authorized
	 * user chosen? Contains an array of index values for options.
	 *
	 * Omitted when no user token provided.
	 */
	public array $own_votes;

	/**
	 * Description: When called with a user token, has the authorized user voted?
	 *
	 * Omitted when no user token provided.
	 */
	public bool $voted;

	/** Description: How many unique accounts have voted on a multiple-choice poll. */
	public int $voters_count;

	/** Description: How many votes have been received. */
	public int $votes_count;
}
