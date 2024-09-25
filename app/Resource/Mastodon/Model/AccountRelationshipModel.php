<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for accountRelationship
 * title Relationship represents a relationship between accounts.
 */
class AccountRelationshipModel extends MastodonModel
{
	/** Description: This account is blocking you. */
	public bool $blocked_by;

	/** Description: You are blocking this account. */
	public bool $blocking;

	/** Description: You are blocking this account's domain. */
	public bool $domain_blocking;

	/** Description: You are featuring this account on your profile. */
	public bool $endorsed;

	/** Description: This account follows you. */
	public bool $followed_by;

	/** Description: You are following this account. */
	public bool $following;

	/**
	 * Example: 01FBW9XGEP7G6K88VY4S9MPE1R
	 * Description: The account id.
	 */
	public string $id;

	/** Description: You are muting this account. */
	public bool $muting;

	/** Description: You are muting notifications from this account. */
	public bool $muting_notifications;

	/** Description: Your note on this account. */
	public string $note;

	/** Description: You are seeing notifications when this account posts. */
	public bool $notifying;

	/** Description: You have requested to follow this account, and the request is pending. */
	public bool $requested;

	/** Description: You are seeing reblogs/boosts from this account in your home timeline. */
	public bool $showing_reblogs;
}
