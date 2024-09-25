<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for statusCreateRequest
 * title StatusCreateRequest models status creation parameters.
 */
class StatusCreateRequestModel extends MastodonModel
{
	/**
	 * Description: Content type to use when parsing this status.
	 * in: formData
	 */
	public string $content_type;

	/**
	 * Description: ID of the status being replied to, if status is a reply.
	 * in: formData
	 */
	public string $in_reply_to_id;

	/**
	 * Description: ISO 639 language code for this status.
	 * in: formData
	 */
	public string $language;

	/**
	 * Description: Array of Attachment ids to be attached as media.
	 * If provided, status becomes optional, and poll cannot be used.
	 *
	 * If the status is being submitted as a form, the key is 'media_ids[]',
	 * but if it's json or xml, the key is 'media_ids'.
	 *
	 * in: formData
	 */
	public array $media_ids;
	public PollRequestModel $poll;

	/**
	 * Description: ISO 8601 Datetime at which to schedule a status.
	 * Providing this parameter will cause ScheduledStatus to be returned instead of Status.
	 * Must be at least 5 minutes in the future.
	 * in: formData
	 */
	public string $scheduled_at;

	/**
	 * Description: Status and attached media should be marked as sensitive.
	 * in: formData
	 */
	public bool $sensitive;

	/**
	 * Description: Text to be shown as a warning or subject before the actual content.
	 * Statuses are generally collapsed behind this field.
	 * in: formData
	 */
	public string $spoiler_text;

	/**
	 * Description: Text content of the status.
	 * If media_ids is provided, this becomes optional.
	 * Attaching a poll is optional while status is provided.
	 * in: formData
	 */
	public string $status;

	/**
	 * Description: Visibility of the posted status.
	 * in: formData
	 */
	public string $visibility;
}
