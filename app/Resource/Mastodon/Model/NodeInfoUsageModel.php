<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for NodeInfoUsage
 * title NodeInfoUsage represents usage information about this server, such as number of users.
 */
class NodeInfoUsageModel extends MastodonModel
{
	public ?int $localPosts;
	public NodeInfoUsersModel $users;
}
