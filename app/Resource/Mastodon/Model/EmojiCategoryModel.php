<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for emojiCategory
 * title EmojiCategory represents a custom emoji category.
 */
class EmojiCategoryModel extends MastodonModel
{
	/** Description: The ID of the custom emoji category. */
	public string $id;

	/** Description: The name of the custom emoji category. */
	public string $name;
}
