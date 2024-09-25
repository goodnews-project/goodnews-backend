<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for updateField
 * title UpdateField is to be used specifically in an UpdateCredentialsRequest.
 */
class UpdateFieldModel extends MastodonModel
{
	/** Description: Name of the field */
	public string $name;

	/** Description: Value of the field */
	public string $value;
}
