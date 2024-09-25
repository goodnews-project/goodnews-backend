<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for mediaDimensions
 * title MediaDimensions models detailed properties of a piece of media.
 */
class MediaDimensionsModel extends MastodonModel
{
	/**
	 * Example: 1.777777778
	 * Description: Aspect ratio of the media.
	 * Equal to width / height.
	 */
	public \number $aspect;

	/**
	 * Example: 1000000
	 * Description: Bitrate of the media in bits per second.
	 */
	public int $bitrate;

	/**
	 * Example: 5.43
	 * Description: Duration of the media in seconds.
	 * Only set for video and audio.
	 */
	public \number $duration;

	/**
	 * Example: 30
	 * Description: Framerate of the media.
	 * Only set for video and gifs.
	 */
	public string $frame_rate;

	/**
	 * Example: 1080
	 * Description: Height of the media in pixels.
	 * Not set for audio.
	 */
	public int $height;

	/**
	 * Example: 1920x1080
	 * Description: Size of the media, in the format `[width]x[height]`.
	 * Not set for audio.
	 */
	public string $size;

	/**
	 * Example: 1920
	 * Description: Width of the media in pixels.
	 * Not set for audio.
	 */
	public int $width;
}
