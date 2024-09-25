<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV2Configuration
 * title Configured values and limits for this instance.
 */
class InstanceV2ConfigurationModel extends MastodonModel
{
	public InstanceConfigurationAccountsModel $accounts;
	public InstanceConfigurationEmojisModel $emojis;
	public InstanceConfigurationMediaAttachmentsModel $media_attachments;
	public InstanceConfigurationPollsModel $polls;
	public InstanceConfigurationStatusesModel $statuses;
	public InstanceV2ConfigurationTranslationModel $translation;
	public InstanceV2URLsModel $urls;
}
