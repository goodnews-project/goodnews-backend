<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for multiStatus
 * title MultiStatus models a multistatus HTTP response body.
 */
class MultiStatusModel extends MastodonModel
{
	public array $data;
	public MultiStatusMetadataModel $metadata;
}
