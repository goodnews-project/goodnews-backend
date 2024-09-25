<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for mediaMeta
 * title MediaMeta models media metadata.
 */
class MediaMetaModel extends MastodonModel
{
	public MediaFocusModel $focus;
	public MediaDimensionsModel $original;
	public MediaDimensionsModel $small;
}
