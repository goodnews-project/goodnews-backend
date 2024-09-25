<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2Users
 * title Usage data related to users on this instance.
 */
class InstanceV2UsersModel extends MastodonModel
{
	/**
	 * Description: The number of active users in the past 4 weeks.
	 * Currently not implemented: will always be 0.
	 */
	public int $active_month;
}
