<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for domain
 */
class DomainModel extends MastodonModel
{
	/**
	 * Example: example.org
	 * Description: The hostname of the domain.
	 */
	public string $domain;

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
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: Time at which this domain was suspended. Key will not be present on open domains.
	 */
	public string $suspended_at;
}
