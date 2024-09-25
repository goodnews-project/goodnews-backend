<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for pollOption
 * title PollOption represents the current vote counts for different poll options.
 */
class PollOptionModel extends MastodonModel
{
	/** Description: The text value of the poll option. String. */
	public string $title;

	/** Description: The number of received votes for this option. */
	public int $votes_count;
}
