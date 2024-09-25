<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for updateSource
 * title UpdateSource is to be used specifically in an UpdateCredentialsRequest.
 */
class UpdateSourceModel extends MastodonModel
{
	/** Description: Default language to use for authored statuses. (ISO 6391) */
	public string $language;

	/** Description: Default post privacy for authored statuses. */
	public string $privacy;

	/** Description: Mark authored statuses as sensitive by default. */
	public bool $sensitive;

	/** Description: Default format for authored statuses (text/plain or text/markdown). */
	public string $status_content_type;
}
