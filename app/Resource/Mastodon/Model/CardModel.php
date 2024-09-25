<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for card
 * title Card represents a rich preview card that is generated using OpenGraph tags from a URL.
 */
class CardModel extends MastodonModel
{
	/**
	 * Example: weewee@buzzfeed.com
	 * Description: The author of the original resource.
	 */
	public string $author_name;

	/**
	 * Example: https://buzzfeed.com/authors/weewee
	 * Description: A link to the author of the original resource.
	 */
	public string $author_url;

	/** Description: A hash computed by the BlurHash algorithm, for generating colorful preview thumbnails when media has not been downloaded yet. */
	public string $blurhash;

	/**
	 * Example: Is water wet? We're not sure. In this article, we ask an expert...
	 * Description: Description of preview.
	 */
	public string $description;

	/** Description: Used for photo embeds, instead of custom html. */
	public string $embed_url;

	/** Description: Height of preview, in pixels. */
	public int $height;

	/** Description: HTML to be used for generating the preview card. */
	public string $html;

	/**
	 * Example: https://example.org/fileserver/preview/thumb.jpg
	 * Description: Preview thumbnail.
	 */
	public string $image;

	/**
	 * Example: Buzzfeed
	 * Description: The provider of the original resource.
	 */
	public string $provider_name;

	/**
	 * Example: https://buzzfeed.com
	 * Description: A link to the provider of the original resource.
	 */
	public string $provider_url;

	/**
	 * Example: Buzzfeed - Is Water Wet?
	 * Description: Title of linked resource.
	 */
	public string $title;

	/**
	 * Example: link
	 * Description: The type of the preview card.
	 */
	public string $type;

	/**
	 * Example: https://buzzfeed.com/some/fuckin/buzzfeed/article
	 * Description: Location of linked resource.
	 */
	public string $url;

	/** Description: Width of preview, in pixels. */
	public int $width;
}
