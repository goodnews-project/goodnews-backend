<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for list
 * title List represents a user-created list of accounts that the user follows.
 */
class ListModel extends MastodonModel
{
	/** Description: The ID of the list. */
	public string $id;

	/**
	 * Description: RepliesPolicy for this list.
	 * followed = Show replies to any followed user
	 * list = Show replies to members of the list
	 * none = Show replies to no one
	 */
	public string $replies_policy;

	/** Description: The user-defined title of the list. */
	public string $title;
}
