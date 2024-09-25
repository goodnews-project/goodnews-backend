<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for notification
 * title Notification represents a notification of an event relevant to the user.
 */
class NotificationModel extends MastodonModel
{
	public AccountModel $account;

	/** Description: The timestamp of the notification (ISO 8601 Datetime) */
	public string $created_at;

	/** Description: The id of the notification in the database. */
	public string $id;
	public StatusModel $status;

	/**
	 * Description: The type of event that resulted in the notification.
	 * follow = Someone followed you
	 * follow_request = Someone requested to follow you
	 * mention = Someone mentioned you in their status
	 * reblog = Someone boosted one of your statuses
	 * favourite = Someone favourited one of your statuses
	 * poll = A poll you have voted in or created has ended
	 * status = Someone you enabled notifications for has posted a status
	 */
	public string $type;
}
