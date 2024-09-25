<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceConfigurationStatuses
 * title InstanceConfigurationStatuses models instance status config parameters.
 */
class InstanceConfigurationStatusesModel extends MastodonModel
{
	/**
	 * Example: 25
	 * Description: Amount of characters clients should assume a url takes up.
	 */
	public int $characters_reserved_per_url;

	/**
	 * Example: 5000
	 * Description: Maximum allowed length of a post on this instance, in characters.
	 */
	public int $max_characters;

	/**
	 * Example: 4
	 * Description: Max number of attachments allowed on a status.
	 */
	public int $max_media_attachments;

	/**
	 * Example: text/plain
	 * text/markdown
	 * Description: List of mime types that it's possible to use for statuses on this instance.
	 */
	public array $supported_mime_types;
}
