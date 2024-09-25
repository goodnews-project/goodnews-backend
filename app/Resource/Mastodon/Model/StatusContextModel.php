<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for statusContext
 * title Context models the tree around a given status.
 */
class StatusContextModel extends MastodonModel
{
	/** Description: Parents in the thread. */
	public array $ancestors;

	/** Description: Children in the thread. */
	public array $descendants;
}
