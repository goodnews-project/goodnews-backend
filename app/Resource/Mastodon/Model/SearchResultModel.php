<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for searchResult
 * title SearchResult models a search result.
 */
class SearchResultModel extends MastodonModel
{
	public array $accounts;

	/** Description: Slice of strings if api v1, slice of tags if api v2. */
	public array $hashtags;
	public array $statuses;
}
