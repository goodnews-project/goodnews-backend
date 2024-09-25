<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceConfigurationPolls
 * title InstanceConfigurationPolls models instance poll config parameters.
 */
class InstanceConfigurationPollsModel extends MastodonModel
{
	/**
	 * Example: 50
	 * Description: Number of characters allowed per option in the poll.
	 */
	public int $max_characters_per_option;

	/**
	 * Example: 2629746
	 * Description: Maximum expiration time of the poll in seconds.
	 */
	public int $max_expiration;

	/**
	 * Example: 4
	 * Description: Number of options permitted in a poll on this instance.
	 */
	public int $max_options;

	/**
	 * Example: 300
	 * Description: Minimum expiration time of the poll in seconds.
	 */
	public int $min_expiration;
}
