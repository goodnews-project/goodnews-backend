<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for multiStatusMetadata
 */
class MultiStatusMetadataModel extends MastodonModel
{
	/** Description: Count of unsuccessful results (!2xx). */
	public int $failure;

	/** Description: Count of successful results (2xx). */
	public int $success;

	/** Description: Success count + failure count. */
	public int $total;
}
