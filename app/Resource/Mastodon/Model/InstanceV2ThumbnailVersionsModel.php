<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2ThumbnailVersions
 * title Links to scaled resolution images, for high DPI screens.
 */
class InstanceV2ThumbnailVersionsModel extends MastodonModel
{
	/**
	 * Description: The URL for the thumbnail image at 1x resolution.
	 * Key/value not set if scaled versions not available.
	 */
	public string $at1x;

	/**
	 * Description: The URL for the thumbnail image at 2x resolution.
	 * Key/value not set if scaled versions not available.
	 */
	public string $at2x;
}
