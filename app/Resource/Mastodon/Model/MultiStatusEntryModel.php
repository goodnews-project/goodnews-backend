<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for multiStatusEntry
 * title MultiStatusEntry models one entry in multistatus data.
 */
class MultiStatusEntryModel extends MastodonModel
{
	/** Description: Message/error message for this entry. */
	public string $message;

	/**
	 * Description: The resource/result for this entry.
	 * Value may be any type, check the docs
	 * per endpoint to see which to expect.
	 */
	public $resource;

	/** Description: HTTP status code of this entry. */
	public int $status;
}
