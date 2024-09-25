<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for Mention
 * title Mention represents a mention of another account.
 */
class MentionModel extends MastodonModel
{
	/**
	 * Example: some_user@example.org
	 * Description: The account URI as discovered via webfinger.
	 * Equal to username for local users, or username@domain for remote users.
	 */
	public string $acct;

	/**
	 * Example: 01FBYJHQWQZAVWFRK9PDYTKGMB
	 * Description: The ID of the mentioned account.
	 */
	public string $id;

	/**
	 * Example: https://example.org/@some_user
	 * Description: The web URL of the mentioned account's profile.
	 */
	public string $url;

	/**
	 * Example: some_user
	 * Description: The username of the mentioned account.
	 */
	public string $username;
}
