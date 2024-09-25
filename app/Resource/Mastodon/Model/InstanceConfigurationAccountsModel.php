<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceConfigurationAccounts
 * title InstanceConfigurationAccounts models instance account config parameters.
 */
class InstanceConfigurationAccountsModel extends MastodonModel
{
	/** Description: Whether or not accounts on this instance are allowed to upload custom CSS for profiles and statuses. */
	public bool $allow_custom_css;

	/**
	 * Description: The maximum number of featured tags allowed for each account.
	 * Currently not implemented, so this is hardcoded to 10.
	 */
	public int $max_featured_tags;

	/**
	 * Description: The maximum number of profile fields allowed for each account.
	 * Currently not configurable, so this is hardcoded to 6. (https://github.com/superseriousbusiness/gotosocial/issues/1876)
	 */
	public int $max_profile_fields;
}
