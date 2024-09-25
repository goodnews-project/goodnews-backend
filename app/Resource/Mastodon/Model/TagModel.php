<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for tag
 * title Tag represents a hashtag used within the content of a status.
 */
class TagModel extends MastodonModel
{
	/**
	 * Description: History of this hashtag's usage.
	 * Currently just a stub, if provided will always be an empty array.
	 */
	public array $history;

	/**
	 * Example: helloworld
	 * Description: The value of the hashtag after the # sign.
	 */
	public string $name;

	/**
	 * Example: https://example.org/tags/helloworld
	 * Description: Web link to the hashtag.
	 */
	public string $url;
}
