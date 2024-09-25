<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for adminReport
 * title AdminReport models the admin view of a report.
 */
class AdminReportModel extends MastodonModel
{
	public AdminAccountInfoModel $account;

	/** Description: Whether an action has been taken by an admin in response to this report. */
	public bool $action_taken;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: If an action was taken, at what time was this done? (ISO 8601 Datetime)
	 * Will be null if not set / no action yet taken.
	 */
	public string $action_taken_at;
	public AdminAccountInfoModel $action_taken_by_account;

	/**
	 * Example: Account was suspended.
	 * Description: If an action was taken, what comment was made by the admin on the taken action?
	 * Will be null if not set / no action yet taken.
	 */
	public string $action_taken_comment;
	public AdminAccountInfoModel $assigned_account;

	/**
	 * Example: spam
	 * Description: Under what category was this report created?
	 */
	public string $category;

	/**
	 * Example: This person has been harassing me.
	 * Description: Comment submitted when the report was created.
	 * Will be empty if no comment was submitted.
	 */
	public string $comment;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: The date when this report was created (ISO 8601 Datetime).
	 */
	public string $created_at;

	/**
	 * Example: 1
	 * Description: Bool to indicate that report should be federated to remote instance.
	 */
	public bool $forwarded;

	/**
	 * Example: 01FBVD42CQ3ZEEVMW180SBX03B
	 * Description: ID of the report.
	 */
	public string $id;

	/**
	 * Description: Array of rules that were broken according to this report.
	 * Will be empty if no rule IDs were submitted with the report.
	 */
	public array $rules;

	/**
	 * Description: Array of  statuses that were submitted along with this report.
	 * Will be empty if no status IDs were submitted with the report.
	 */
	public array $statuses;
	public AdminAccountInfoModel $target_account;

	/**
	 * Example: 2021-07-30T09:20:25+00:00
	 * Description: Time of last action on this report (ISO 8601 Datetime).
	 */
	public string $updated_at;
}
