<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for emoji
 * title Emoji represents a custom emoji.
 */
class EmojiModel extends MastodonModel
{
	/**
	 * Example: blobcats
	 * Description: Used for sorting custom emoji in the picker.
	 */
	public string $category;

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
