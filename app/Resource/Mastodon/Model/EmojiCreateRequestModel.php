<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for emojiCreateRequest
 * title EmojiCreateRequest represents a request to create a custom emoji made through the admin API.
 */
class EmojiCreateRequestModel extends MastodonModel
{
	/**
	 * Description: Category in which to place the new emoji. Will be uncategorized by default.
	 * CategoryName length should not exceed 64 characters.
	 */
	public string $CategoryName;

	/** Description: Image file to use for the emoji. Must be png or gif and no larger than 50kb. */
	public $Image;

	/**
	 * Example: blobcat_uwu
	 * Description: Desired shortcode for the emoji, without surrounding colons. This must be unique for the domain.
	 */
	public string $Shortcode;
}
