<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for swaggerCollection
 * title SwaggerCollection represents an ActivityPub Collection.
 */
class SwaggerCollectionModel extends MastodonModel
{
	/**
	 * Example: https://www.w3.org/ns/activitystreams
	 * Description: ActivityStreams JSON-LD context.
	 * A string or an array of strings, or more
	 * complex nested items.
	 */
	public $atcontext;
	public SwaggerCollectionPageModel $first;

	/**
	 * Example: https://example.org/users/some_user/statuses/106717595988259568/replies
	 * Description: ActivityStreams ID.
	 */
	public string $id;
	public SwaggerCollectionPageModel $last;

	/**
	 * Example: Collection
	 * Description: ActivityStreams type.
	 */
	public string $type;
}
