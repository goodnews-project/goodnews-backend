<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2Contact
 * title Hints related to contacting a representative of the instance.
 */
class InstanceV2ContactModel extends MastodonModel
{
	public AccountModel $account;

	/**
	 * Example: someone@example.org
	 * Description: An email address that can be messaged regarding inquiries or issues.
	 * Empty string if no email address set.
	 */
	public string $email;
}
