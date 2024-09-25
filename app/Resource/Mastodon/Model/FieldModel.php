<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for field
 * title Field represents a name/value pair to display on an account's profile.
 */
class FieldModel extends MastodonModel
{
	/**
	 * Example: pronouns
	 * Description: The key/name of this field.
	 */
	public string $name;

	/**
	 * Example: they/them
	 * Description: The value of this field.
	 */
	public string $value;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: If this field has been verified, when did this occur? (ISO 8601 Datetime).
	 */
	public string $verified_at;
}
