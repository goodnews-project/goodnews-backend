<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for emojiUpdateRequest
 * title EmojiUpdateRequest represents a request to update a custom emoji, made through the admin API.
 */
class EmojiUpdateRequestModel extends MastodonModel
{
	/** Description: Category in which to place the emoji. */
	public string $CategoryName;

	/**
	 * Description: Image file to use for the emoji.
	 * Must be png or gif and no larger than 50kb.
	 */
	public $Image;

	/**
	 * Example: blobcat_uwu
	 * Description: Desired shortcode for the emoji, without surrounding colons. This must be unique for the domain.
	 */
	public string $Shortcode;
	public EmojiUpdateTypeModel $type;
}
