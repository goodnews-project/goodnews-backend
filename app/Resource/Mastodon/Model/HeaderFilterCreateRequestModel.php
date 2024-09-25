<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for headerFilterCreateRequest
 * title HeaderFilterRequest is the form submitted as a POST to create a new header filter entry (allow / block).
 */
class HeaderFilterCreateRequestModel extends MastodonModel
{
	/** Description: The HTTP header to match against (e.g. User-Agent). */
	public string $header;

	/** Description: The header value matching regular expression. */
	public string $regex;
}
