<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceRuleCreateRequest
 * title InstanceRuleCreateRequest represents a request to create a new instance rule, made through the admin API.
 */
class InstanceRuleCreateRequestModel extends MastodonModel
{
	public string $Text;
}
