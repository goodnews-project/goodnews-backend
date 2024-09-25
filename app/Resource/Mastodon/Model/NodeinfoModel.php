<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for nodeinfo
 * title Nodeinfo represents a version 2.1 or version 2.0 nodeinfo schema.
 */
class NodeinfoModel extends MastodonModel
{
	/** Description: Free form key value pairs for software specific values. Clients should not rely on any specific key present. */
	public object $metadata;

	/** Description: Whether this server allows open self-registration. */
	public bool $openRegistrations;

	/** Description: The protocols supported on this server. */
	public array $protocols;
	public NodeInfoServicesModel $services;
	public NodeInfoSoftwareModel $software;
	public NodeInfoUsageModel $usage;

	/**
	 * Example: 2.0
	 * Description: The schema version
	 */
	public string $version;
}
