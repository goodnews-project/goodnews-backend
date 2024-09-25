<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV1URLs
 * title InstanceV1URLs models instance-relevant URLs for client application consumption.
 */
class InstanceV1URLsModel extends MastodonModel
{
	/**
	 * Example: wss://example.org
	 * Description: Websockets address for status and notification streaming.
	 */
	public string $streaming_api;
}
