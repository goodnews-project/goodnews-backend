<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2URLs
 * title InstanceV2URLs models instance-relevant URLs for client application consumption.
 */
class InstanceV2URLsModel extends MastodonModel
{
	/**
	 * Example: wss://example.org
	 * Description: Websockets address for status and notification streaming.
	 */
	public string $streaming;
}
