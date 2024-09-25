<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for oauthToken
 * title Token represents an OAuth token used for authenticating with the GoToSocial API and performing actions.
 */
class OauthTokenModel extends MastodonModel
{
	/** Description: Access token used for authorization. */
	public string $access_token;

	/**
	 * Example: 1627644520
	 * Description: When the OAuth token was generated (UNIX timestamp seconds).
	 */
	public int $created_at;

	/**
	 * Example: read write admin
	 * Description: OAuth scopes granted by this token, space-separated.
	 */
	public string $scope;

	/**
	 * Example: bearer
	 * Description: OAuth token type. Will always be 'Bearer'.
	 */
	public string $token_type;
}
