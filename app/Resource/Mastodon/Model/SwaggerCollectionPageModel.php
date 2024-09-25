<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for swaggerCollectionPage
 * title SwaggerCollectionPage represents one page of a collection.
 */
class SwaggerCollectionPageModel extends MastodonModel
{
	/**
	 * Example: https://example.org/users/some_user/statuses/106717595988259568/replies?page=true
	 * Description: ActivityStreams ID.
	 */
	public string $id;

	/**
	 * Example: https://example.org/users/some_other_user/statuses/086417595981111564
	 * https://another.example.com/users/another_user/statuses/01FCN8XDV3YG7B4R42QA6YQZ9R
	 * Description: Items on this page.
	 */
	public array $items;

	/**
	 * Example: https://example.org/users/some_user/statuses/106717595988259568/replies?only_other_accounts=true&page=true
	 * Description: Link to the next page.
	 */
	public string $next;

	/**
	 * Example: https://example.org/users/some_user/statuses/106717595988259568/replies
	 * Description: Collection this page belongs to.
	 */
	public string $partOf;

	/**
	 * Example: CollectionPage
	 * Description: ActivityStreams type.
	 */
	public string $type;
}
