<?php 
namespace App\Resource\Mastodon\Model;

/**
 * Class generate for mediaFocus
 * title MediaFocus models the focal point of a piece of media.
 */
class MediaFocusModel extends MastodonModel
{
	/**
	 * Description: x position of the focus
	 * should be between -1 and 1
	 */
	public \number $x;

	/**
	 * Description: y position of the focus
	 * should be between -1 and 1
	 */
	public \number $y;
}
