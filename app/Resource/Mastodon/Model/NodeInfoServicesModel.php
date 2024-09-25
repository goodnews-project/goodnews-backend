<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for NodeInfoServices
 * title NodeInfoServices represents inbound and outbound services that this node offers connections to.
 */
class NodeInfoServicesModel extends MastodonModel
{
	public array $inbound;
	public array $outbound;
}
