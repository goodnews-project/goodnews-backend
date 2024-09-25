<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for announcement
 * title Announcement models an admin announcement for the instance.
 */
class AnnouncementModel extends MastodonModel
{
	/** Description: Announcement doesn't have begin time and end time, but begin day and end day. */
	public bool $all_day;

	/**
	 * Example: <p>This is an announcement. No malarky.</p>
	 * Description: The body of the announcement.
	 * Should be HTML formatted.
	 */
	public string $content;

	/** Description: Emojis used in this announcement. */
	public array $emoji;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the announcement should stop being displayed (ISO 8601 Datetime).
	 * If the announcement has no end time, this will be omitted or empty.
	 */
	public string $ends_at;

	/**
	 * Example: 01FC30T7X4TNCZK0TH90QYF3M4
	 * Description: The ID of the announcement.
	 */
	public string $id;

	/** Description: Mentions this announcement contains. */
	public array $mentions;

	/**
	 * Description: Announcement is 'published', ie., visible to users.
	 * Announcements that are not published should be shown only to admins.
	 */
	public bool $published;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the announcement was first published (ISO 8601 Datetime).
	 */
	public string $published_at;

	/** Description: Reactions to this announcement. */
	public array $reactions;

	/** Description: Requesting account has seen this announcement. */
	public bool $read;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the announcement should begin to be displayed (ISO 8601 Datetime).
	 * If the announcement has no start time, this will be omitted or empty.
	 */
	public string $starts_at;

	/** Description: Statuses contained in this announcement. */
	public array $statuses;

	/** Description: Tags used in this announcement. */
	public array $tags;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: When the announcement was last updated (ISO 8601 Datetime).
	 */
	public string $updated_at;
}
