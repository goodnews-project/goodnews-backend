<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for wellKnownResponse
 * title WellKnownResponse represents the response to either a webfinger request for an 'acct' resource, or a request to nodeinfo.
 * For example, it would be returned from https://example.org/.well-known/webfinger?resource=acct:some_username@example.org
 */
class WellKnownResponseModel extends MastodonModel
{
	public array $aliases;
	public array $links;
	public string $subject;
}
