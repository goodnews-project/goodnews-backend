<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for advancedVisibilityFlagsForm
 */
class AdvancedVisibilityFlagsFormModel extends MastodonModel
{
	/** Description: This status can be boosted/reblogged. */
	public bool $boostable;

	/** Description: This status will be federated beyond the local timeline(s). */
	public bool $federated;

	/** Description: This status can be liked/faved. */
	public bool $likeable;

	/** Description: This status can be replied to. */
	public bool $replyable;
}
