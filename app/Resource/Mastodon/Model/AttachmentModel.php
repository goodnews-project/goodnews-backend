<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for attachment
 * title Attachment models a media attachment.
 */
class AttachmentModel extends MastodonModel
{
	/**
	 * Description: A hash computed by the BlurHash algorithm, for generating colorful preview thumbnails when media has not been downloaded yet.
	 * See https://github.com/woltapp/blurhash
	 */
	public string $blurhash;

	/**
	 * Example: This is a picture of a kitten.
	 * Description: Alt text that describes what is in the media attachment.
	 */
	public string $description;

	/**
	 * Example: 01FC31DZT1AYWDZ8XTCRWRBYRK
	 * Description: The ID of the attachment.
	 */
	public string $id;
	public MediaMetaModel $meta;

	/**
	 * Example: https://some-other-server.org/attachments/small/ahhhhh.jpeg
	 * Description: The location of a scaled-down preview of the attachment on the remote server.
	 * Only defined for instances other than our own.
	 */
	public string $preview_remote_url;

	/**
	 * Example: https://example.org/fileserver/some_id/attachments/some_id/small/attachment.jpeg
	 * Description: The location of a scaled-down preview of the attachment.
	 */
	public string $preview_url;

	/**
	 * Example: https://some-other-server.org/attachments/original/ahhhhh.jpeg
	 * Description: The location of the full-size original attachment on the remote server.
	 * Only defined for instances other than our own.
	 */
	public string $remote_url;

	/**
	 * Description: A shorter URL for the attachment.
	 * In our case, we just give the URL again since we don't create smaller URLs.
	 */
	public string $text_url;

	/**
	 * Example: image
	 * Description: The type of the attachment.
	 */
	public string $type;

	/**
	 * Example: https://example.org/fileserver/some_id/attachments/some_id/original/attachment.jpeg
	 * Description: The location of the original full-size attachment.
	 */
	public string $url;
}
