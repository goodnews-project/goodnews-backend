<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2
 * title InstanceV2 models information about this instance.
 */
class InstanceV2Model extends MastodonModel
{
	/**
	 * Example: example.org
	 * Description: The domain of accounts on this instance.
	 * This will not necessarily be the same as
	 * domain.
	 */
	public string $account_domain;
	public InstanceV2ConfigurationModel $configuration;
	public InstanceV2ContactModel $contact;

	/**
	 * Description: Description of the instance.
	 *
	 * Should be HTML formatted, but might be plaintext.
	 *
	 * This should be displayed on the 'about' page for an instance.
	 */
	public string $description;

	/** Description: Raw (unparsed) version of description. */
	public string $description_text;

	/**
	 * Example: gts.example.org
	 * Description: The domain of the instance.
	 */
	public string $domain;

	/**
	 * Example: en
	 * Description: Primary languages of the instance + moderators/admins.
	 */
	public array $languages;
	public InstanceV2RegistrationsModel $registrations;

	/** Description: An itemized list of rules for this instance. */
	public array $rules;

	/**
	 * Example: https://github.com/superseriousbusiness/gotosocial
	 * Description: The URL for the source code of the software running on this instance, in keeping with AGPL license requirements.
	 */
	public string $source_url;

	/** Description: Terms and conditions for accounts on this instance. */
	public string $terms;

	/** Description: Raw (unparsed) version of terms. */
	public string $terms_text;
	public InstanceV2ThumbnailModel $thumbnail;

	/**
	 * Example: GoToSocial Example Instance
	 * Description: The title of the instance.
	 */
	public string $title;
	public InstanceV2UsageModel $usage;

	/**
	 * Example: 0.1.1 cb85f65
	 * Description: The version of GoToSocial installed on the instance.
	 *
	 * This will contain at least a semantic version number.
	 *
	 * It may also contain, after a space, the short git commit ID of the running software.
	 */
	public string $version;
}
