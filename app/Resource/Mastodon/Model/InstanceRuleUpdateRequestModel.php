<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceRuleUpdateRequest
 * title InstanceRuleUpdateRequest represents a request to update the text of an instance rule, made through the admin API.
 */
class InstanceRuleUpdateRequestModel extends MastodonModel
{
	public string $ID;
	public string $Text;
}
