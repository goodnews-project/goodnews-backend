<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for pollRequest
 * title PollRequest models a request to create a poll.
 */
class PollRequestModel extends MastodonModel
{
	/**
	 * Description: Duration the poll should be open, in seconds.
	 * If provided, media_ids cannot be used, and poll[options] must be provided.
	 */
	public int $ExpiresIn;

	/**
	 * Description: Duration the poll should be open, in seconds.
	 * If provided, media_ids cannot be used, and poll[options] must be provided.
	 */
	public $expires_in;

	/** Description: Hide vote counts until the poll ends. */
	public bool $hide_totals;

	/** Description: Allow multiple choices on this poll. */
	public bool $multiple;

	/**
	 * Description: Array of possible answers.
	 * If provided, media_ids cannot be used, and poll[expires_in] must be provided.
	 * name: poll[options]
	 */
	public array $options;
}
