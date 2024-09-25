<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for swaggerFeaturedCollection
 * title SwaggerFeaturedCollection represents an ActivityPub OrderedCollection.
 */
class SwaggerFeaturedCollectionModel extends MastodonModel
{
	/**
	 * Example: https://www.w3.org/ns/activitystreams
	 * Description: ActivityStreams JSON-LD context.
	 * A string or an array of strings, or more
	 * complex nested items.
	 */
	public $atcontext;

	/**
	 * Example: 2
	 * Description: Number of items in this collection.
	 */
	public int $TotalItems;

	/**
	 * Example: https://example.org/users/some_user/collections/featured
	 * Description: ActivityStreams ID.
	 */
	public string $id;

	/**
	 * Example: ['https://example.org/users/some_user/statuses/01GSZ0F7Q8SJKNRF777GJD271R', 'https://example.org/users/some_user/statuses/01GSZ0G012CBQ7TEKX689S3QRE']
	 * Description: List of status URIs.
	 */
	public array $items;

	/**
	 * Example: OrderedCollection
	 * Description: ActivityStreams type.
	 */
	public string $type;
}
