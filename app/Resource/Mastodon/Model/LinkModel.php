<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for Link
 * title Link represents one 'link' in a slice of links returned from a lookup request.
 */
class LinkModel extends MastodonModel
{
	public string $href;
	public string $rel;
	public string $template;
	public string $type;
}
