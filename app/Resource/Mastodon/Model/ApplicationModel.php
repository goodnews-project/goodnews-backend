<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for application
 * title Application models an api application.
 */
class ApplicationModel extends MastodonModel
{
	/** Description: Client ID associated with this application. */
	public string $client_id;

	/** Description: Client secret associated with this application. */
	public string $client_secret;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: The ID of the application.
	 */
	public string $id;

	/**
	 * Example: Tusky
	 * Description: The name of the application.
	 */
	public string $name;

	/**
	 * Example: https://example.org/callback?some=query
	 * Description: Post-authorization redirect URI for the application (OAuth2).
	 */
	public string $redirect_uri;

	/** Description: Push API key for this application. */
	public string $vapid_key;

	/**
	 * Example: https://tusky.app
	 * Description: The website associated with the application (url)
	 */
	public string $website;
}
