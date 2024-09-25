<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for account
 * title Account models a fediverse account.
 */
class AccountModel extends MastodonModel
{
	/**
	 * Example: some_user@example.org
	 * Description: The account URI as discovered via webfinger.
	 * Equal to username for local users, or username@domain for remote users.
	 */
	public string $acct;

	/**
	 * Example: https://example.org/media/some_user/avatar/original/avatar.jpeg
	 * Description: Web location of the account's avatar.
	 */
	public string $avatar;

	/**
	 * Example: https://example.org/media/some_user/avatar/static/avatar.png
	 * Description: Web location of a static version of the account's avatar.
	 * Only relevant when the account's main avatar is a video or a gif.
	 */
	public string $avatar_static;

	/** Description: Account identifies as a bot. */
	public bool $bot;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the account was created (ISO 8601 Datetime).
	 */
	public string $created_at;

	/** Description: CustomCSS to include when rendering this account's profile or statuses. */
	public string $custom_css;

	/** Description: Account has opted into discovery features. */
	public bool $discoverable;

	/**
	 * Example: big jeff (he/him)
	 * Description: The account's display name.
	 */
	public string $display_name;

	/** Description: Array of custom emojis used in this account's note or display name. */
	public array $emojis;

	/** Description: Account has enabled RSS feed. */
	public bool $enable_rss;

	/** Description: Additional metadata attached to this account's profile. */
	public array $fields;

	/** Description: Number of accounts following this account, according to our instance. */
	public int $followers_count;

	/** Description: Number of account's followed by this account, according to our instance. */
	public int $following_count;

	/**
	 * Example: https://example.org/media/some_user/header/original/header.jpeg
	 * Description: Web location of the account's header image.
	 */
	public string $header;

	/**
	 * Example: https://example.org/media/some_user/header/static/header.png
	 * Description: Web location of a static version of the account's header.
	 * Only relevant when the account's main header is a video or a gif.
	 */
	public string $header_static;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: The account id.
	 */
	public string $id;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the account's most recent status was posted (ISO 8601 Datetime).
	 */
	public string $last_status_at;

	/** Description: Account manually approves follow requests. */
	public bool $locked;
	public ?AccountModel $moved;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: If this account has been muted, when will the mute expire (ISO 8601 Datetime).
	 */
	public string $mute_expires_at;

	/** Description: Bio/description of this account. */
	public string $note;
	public AccountRoleModel $role;
	public SourceModel $source;

	/** Description: Number of statuses posted by this account, according to our instance. */
	public int $statuses_count;

	/** Description: Account has been suspended by our instance. */
	public bool $suspended;

	/**
	 * Example: https://example.org/@some_user
	 * Description: Web location of the account's profile page.
	 */
	public string $url;

	/**
	 * Example: some_user
	 * Description: The username of the account, not including domain.
	 */
	public string $username;
}
