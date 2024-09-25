<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2Registrations
 * title Information about registering for this instance.
 */
class InstanceV2RegistrationsModel extends MastodonModel
{
	/**
	 * Example: 1
	 * Description: Whether registrations require moderator approval.
	 */
	public bool $approval_required;

	/** Description: Whether registrations are enabled. */
	public bool $enabled;

	/**
	 * Example: <p>Registrations are currently closed on example.org because of spam bots!</p>
	 * Description: A custom message (html string) to be shown when registrations are closed.
	 * Value will be null if no message is set.
	 */
	public string $message;
}
