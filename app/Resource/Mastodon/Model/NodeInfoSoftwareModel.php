<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for NodeInfoSoftware
 * title NodeInfoSoftware represents the name and version number of the software of this node.
 */
class NodeInfoSoftwareModel extends MastodonModel
{
	/** Example: gotosocial */
	public string $name;

	/** Example: 0.1.2 1234567 */
	public string $version;
}
