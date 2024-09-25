<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for announcementReaction
 * title AnnouncementReaction models a user reaction to an announcement.
 */
class AnnouncementReactionModel extends MastodonModel
{
	/**
	 * Example: 5
	 * Description: The total number of users who have added this reaction.
	 */
	public int $count;

	/** Description: This reaction belongs to the account viewing it. */
	public bool $me;

	/**
	 * Example: blobcat_uwu
	 * Description: The emoji used for the reaction. Either a unicode emoji, or a custom emoji's shortcode.
	 */
	public string $name;

	/**
	 * Example: https://example.org/custom_emojis/statuc/blobcat_uwu.png
	 * Description: Web link to a non-animated image of the custom emoji.
	 * Empty for unicode emojis.
	 */
	public string $static_url;

	/**
	 * Example: https://example.org/custom_emojis/original/blobcat_uwu.png
	 * Description: Web link to the image of the custom emoji.
	 * Empty for unicode emojis.
	 */
	public string $url;
}
