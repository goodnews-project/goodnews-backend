<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceConfigurationMediaAttachments
 * title InstanceConfigurationMediaAttachments models instance media attachment config parameters.
 */
class InstanceConfigurationMediaAttachmentsModel extends MastodonModel
{
	/**
	 * Example: 16777216
	 * Description: Max allowed image size in pixels as height*width.
	 *
	 * GtS doesn't set a limit on this, but for compatibility
	 * we give Mastodon's 4096x4096px value here.
	 */
	public int $image_matrix_limit;

	/**
	 * Example: 2097152
	 * Description: Max allowed image size in bytes
	 */
	public int $image_size_limit;

	/**
	 * Example: image/jpeg
	 * image/gif
	 * Description: List of mime types that it's possible to upload to this instance.
	 */
	public array $supported_mime_types;

	/**
	 * Example: 60
	 * Description: Max allowed video frame rate.
	 */
	public int $video_frame_rate_limit;

	/**
	 * Example: 16777216
	 * Description: Max allowed video size in pixels as height*width.
	 *
	 * GtS doesn't set a limit on this, but for compatibility
	 * we give Mastodon's 4096x4096px value here.
	 */
	public int $video_matrix_limit;

	/**
	 * Example: 10485760
	 * Description: Max allowed video size in bytes
	 */
	public int $video_size_limit;
}
