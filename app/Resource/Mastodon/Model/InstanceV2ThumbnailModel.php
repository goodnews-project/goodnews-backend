<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2Thumbnail
 * title An image used to represent this instance.
 */
class InstanceV2ThumbnailModel extends MastodonModel
{
	/**
	 * Example: UeKUpFxuo~R%0nW;WCnhF6RjaJt757oJodS$
	 * Description: A hash computed by the BlurHash algorithm, for generating colorful preview thumbnails when media has not been downloaded yet.
	 * Key/value not set if no blurhash available.
	 */
	public string $blurhash;

	/**
	 * Example: picture of a cute lil' friendly sloth
	 * Description: Description of the instance thumbnail.
	 * Key/value not set if no description available.
	 */
	public string $thumbnail_description;

	/**
	 * Example: image/png
	 * Description: MIME type of the instance thumbnail.
	 * Key/value not set if thumbnail image type unknown.
	 */
	public string $thumbnail_type;

	/**
	 * Example: https://example.org/fileserver/01BPSX2MKCRVMD4YN4D71G9CP5/attachment/original/01H88X0KQ2DFYYDSWYP93VDJZA.png
	 * Description: The URL for the thumbnail image.
	 */
	public string $url;
	public InstanceV2ThumbnailVersionsModel $versions;
}
