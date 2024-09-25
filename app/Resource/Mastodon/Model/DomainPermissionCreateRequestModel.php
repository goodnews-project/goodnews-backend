<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for domainPermissionCreateRequest
 * title DomainPermissionRequest is the form submitted as a POST to create a new domain permission entry (allow/block).
 */
class DomainPermissionCreateRequestModel extends MastodonModel
{
	/**
	 * Example: example.org
	 * Description: A single domain for which this permission request should apply.
	 * Only used if import=true is NOT specified or if import=false.
	 */
	public string $domain;

	/**
	 * Description: A list of domains for which this permission request should apply.
	 * Only used if import=true is specified.
	 */
	public $domains;

	/**
	 * Description: Obfuscate the domain name when displaying this permission entry publicly.
	 * Ie., instead of 'example.org' show something like 'e**mpl*.or*'.
	 */
	public bool $obfuscate;

	/**
	 * Example: don't like 'em!!!!
	 * Description: Private comment for other admins on why this permission entry was created.
	 */
	public string $private_comment;

	/**
	 * Example: foss dorks 😫
	 * Description: Public comment on why this permission entry was created.
	 * Will be visible to requesters at /api/v1/instance/peers if this endpoint is exposed.
	 */
	public string $public_comment;
}
