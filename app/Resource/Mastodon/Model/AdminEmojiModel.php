<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for adminEmoji
 * title AdminEmoji models the admin view of a custom emoji.
 */
class AdminEmojiModel extends MastodonModel
{
	/**
	 * Example: blobcats
	 * Description: Used for sorting custom emoji in the picker.
	 */
	public string $category;

	/**
	 * Example: image/png
	 * Description: The MIME content type of the emoji.
	 */
	public string $content_type;

	/** Description: True if this emoji has been disabled by an admin action. */
	public bool $disabled;

	/**
	 * Example: example.org
	 * Description: The domain from which the emoji originated. Only defined for remote domains, otherwise key will not be set.
	 */
	public string $domain;

	/**
	 * Example: 01GEM7SFDZ7GZNRXFVZ3X4E4N1
	 * Description: The ID of the emoji.
	 */
	public string $id;

	/**
	 * Example: blobcat_uwu
	 * Description: The name of the custom emoji.
	 */
	public string $shortcode;

	/**
	 * Example: https://example.org/fileserver/emojis/blogcat_uwu.png
	 * Description: A link to a static copy of the custom emoji.
	 */
	public string $static_url;

	/**
	 * Example: 69420
	 * Description: The total file size taken up by the emoji in bytes, including static and animated versions.
	 */
	public int $total_file_size;

	/**
	 * Example: 2022-10-05T09:21:26.419Z
	 * Description: Time when the emoji image was last updated.
	 */
	public string $updated_at;

	/**
	 * Example: https://example.org/emojis/016T5Q3SQKBT337DAKVSKNXXW1
	 * Description: The ActivityPub URI of the emoji.
	 */
	public string $uri;

	/**
	 * Example: https://example.org/fileserver/emojis/blogcat_uwu.gif
	 * Description: Web URL of the custom emoji.
	 */
	public string $url;

	/**
	 * Example: 1
	 * Description: Emoji is visible in the emoji picker of the instance.
	 */
	public bool $visible_in_picker;
}
