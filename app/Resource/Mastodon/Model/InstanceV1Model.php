<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV1
 * title InstanceV1 models information about this instance.
 */
class InstanceV1Model extends MastodonModel
{
	/**
	 * Example: example.org
	 * Description: The domain of accounts on this instance.
	 * This will not necessarily be the same as
	 * simply the Host part of the URI.
	 */
	public string $account_domain;

	/** Description: New account registrations require admin approval. */
	public bool $approval_required;
	public InstanceV1ConfigurationModel $configuration;
	public AccountModel $contact_account;

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
	 * Example: admin@example.org
	 * Description: An email address that may be used for inquiries.
	 */
	public string $email;

	/** Description: Invites are enabled on this instance. */
	public bool $invites_enabled;

	/**
	 * Example: en
	 * Description: Primary language of the instance.
	 */
	public array $languages;

	/**
	 * Example: 5000
	 * Description: Maximum allowed length of a post on this instance, in characters.
	 *
	 * This is provided for compatibility with Tusky and other apps.
	 */
	public int $max_toot_chars;

	/** Description: New account registrations are enabled on this instance. */
	public bool $registrations;

	/** Description: An itemized list of rules for this instance. */
	public array $rules;

	/**
	 * Description: A shorter description of the instance.
	 *
	 * Should be HTML formatted, but might be plaintext.
	 *
	 * This should be displayed on the instance splash/landing page.
	 */
	public string $short_description;

	/** Description: Raw (unparsed) version of short description. */
	public string $short_description_text;

	/** Description: Statistics about the instance: number of posts, accounts, etc. */
	public object $stats;

	/** Description: Terms and conditions for accounts on this instance. */
	public string $terms;

	/** Description: Raw (unparsed) version of terms. */
	public string $terms_text;

	/**
	 * Example: https://example.org/files/instance/thumbnail.jpeg
	 * Description: URL of the instance avatar/banner image.
	 */
	public string $thumbnail;

	/**
	 * Example: picture of a cute lil' friendly sloth
	 * Description: Description of the instance thumbnail.
	 */
	public string $thumbnail_description;

	/**
	 * Example: image/png
	 * Description: MIME type of the instance thumbnail.
	 */
	public string $thumbnail_type;

	/**
	 * Example: GoToSocial Example Instance
	 * Description: The title of the instance.
	 */
	public string $title;

	/**
	 * Example: https://gts.example.org
	 * Description: The URI of the instance.
	 */
	public string $uri;
	public InstanceV1URLsModel $urls;

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
