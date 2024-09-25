<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for domainKeysExpireRequest
 * title DomainBlockCreateRequest is the form submitted as a POST to /api/v1/admin/domain_keys_expire to expire a domain's public keys.
 */
class DomainKeysExpireRequestModel extends MastodonModel
{
	/** Description: hostname/domain to expire keys for. */
	public string $domain;
}
