<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for InstanceConfigurationEmojis
 * title InstanceConfigurationEmojis models instance emoji config parameters.
 */
class InstanceConfigurationEmojisModel extends MastodonModel
{
	/**
	 * Example: 51200
	 * Description: Max allowed emoji image size in bytes.
	 */
	public int $emoji_size_limit;
}
