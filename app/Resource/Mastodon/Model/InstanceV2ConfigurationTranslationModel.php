<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2ConfigurationTranslation
 * title Hints related to translation.
 */
class InstanceV2ConfigurationTranslationModel extends MastodonModel
{
	/**
	 * Description: Whether the Translations API is available on this instance.
	 * Not implemented so this value is always false.
	 */
	public bool $enabled;
}
