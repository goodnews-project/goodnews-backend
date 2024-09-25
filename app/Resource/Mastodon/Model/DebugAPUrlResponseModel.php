<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for debugAPUrlResponse
 */
class DebugAPUrlResponseModel extends MastodonModel
{
	/** Description: HTTP headers used in the outgoing request. */
	public object $request_headers;

	/** Description: Remote AP URL that was requested. */
	public string $request_url;

	/**
	 * Description: Body returned from the remote instance.
	 * Will be stringified bytes; may be JSON,
	 * may be an error, may be both!
	 */
	public string $response_body;

	/** Description: HTTP response code returned from the remote instance. */
	public int $response_code;

	/** Description: HTTP headers returned from the remote instance. */
	public object $response_headers;
}
