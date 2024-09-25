<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for instanceV1Configuration
 * title InstanceV1Configuration models instance configuration parameters.
 */
class InstanceV1ConfigurationModel extends MastodonModel
{
	public InstanceConfigurationAccountsModel $accounts;
	public InstanceConfigurationEmojisModel $emojis;
	public InstanceConfigurationMediaAttachmentsModel $media_attachments;
	public InstanceConfigurationPollsModel $polls;
	public InstanceConfigurationStatusesModel $statuses;
}
