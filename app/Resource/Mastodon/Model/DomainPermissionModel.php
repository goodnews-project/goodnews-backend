<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for domainPermission
 * title DomainPermission represents a permission applied to one domain (explicit block/allow).
 */
class DomainPermissionModel extends MastodonModel
{
	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: Time at which the permission entry was created (ISO 8601 Datetime).
	 */
	public string $created_at;

	/**
	 * Example: 01FBW2758ZB6PBR200YPDDJK4C
	 * Description: ID of the account that created this domain permission entry.
	 */
	public string $created_by;

	/**
	 * Example: example.org
	 * Description: The hostname of the domain.
	 */
	public string $domain;

	/**
	 * Example: 01FBW21XJA09XYX51KV5JVBW0F
	 * Description: The ID of the domain permission entry.
	 */
	public string $id;

	/** Description: Obfuscate the domain name when serving this domain permission entry publicly. */
	public bool $obfuscate;

	/**
	 * Example: they are poopoo
	 * Description: Private comment for this permission entry, visible to this instance's admins only.
	 */
	public string $private_comment;

	/**
	 * Example: they smell
	 * Description: If the domain is blocked, what's the publicly-stated reason for the block.
	 */
	public string $public_comment;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: Time at which this domain was silenced. Key will not be present on open domains.
	 */
	public string $silenced_at;

	/**
	 * Example: 01FBW25TF5J67JW3HFHZCSD23K
	 * Description: If applicable, the ID of the subscription that caused this domain permission entry to be created.
	 */
	public string $subscription_id;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: Time at which this domain was suspended. Key will not be present on open domains.
	 */
	public string $suspended_at;
}
