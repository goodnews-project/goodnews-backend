<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for adminAccountInfo
 * title AdminAccountInfo models the admin view of an account's details.
 */
class AdminAccountInfoModel extends MastodonModel
{
	public AccountModel $account;

	/** Description: Whether the account is currently approved. */
	public bool $approved;

	/** Description: Whether the account has confirmed their email address. */
	public bool $confirmed;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the account was first discovered. (ISO 8601 Datetime)
	 */
	public string $created_at;

	/** Description: The ID of the application that created this account. */
	public string $created_by_application_id;

	/** Description: Whether the account is currently disabled. */
	public bool $disabled;

	/**
	 * Example: example.org
	 * Description: The domain of the account.
	 * Null for local accounts.
	 */
	public string $domain;

	/**
	 * Example: someone@somewhere.com
	 * Description: The email address associated with the account.
	 * Empty string for remote accounts or accounts with
	 * no known email address.
	 */
	public string $email;

	/**
	 * Example: 01GQ4PHNT622DQ9X95XQX4KKNR
	 * Description: The ID of the account in the database.
	 */
	public string $id;

	/**
	 * Example: Pleaaaaaaaaaaaaaaase!!
	 * Description: The reason given when requesting an invite.
	 * Null if not known / remote account.
	 */
	public string $invite_request;

	/** Description: The ID of the account that invited this user */
	public string $invited_by_account_id;

	/**
	 * Example: 192.0.2.1
	 * Description: The IP address last used to login to this account.
	 * Null if not known.
	 */
	public string $ip;

	/**
	 * Description: All known IP addresses associated with this account.
	 * NOT IMPLEMENTED (will always be empty array).
	 */
	public array $ips;

	/**
	 * Example: en
	 * Description: The locale of the account. (ISO 639 Part 1 two-letter language code)
	 */
	public string $locale;
	public AccountRoleModel $role;

	/** Description: Whether the account is currently silenced */
	public bool $silenced;

	/** Description: Whether the account is currently suspended. */
	public bool $suspended;

	/**
	 * Example: dril
	 * Description: The username of the account.
	 */
	public string $username;
}
