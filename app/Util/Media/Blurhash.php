<?php

namespace App\Util\Media;

use kornrunner\Blurhash\Blurhash as BlurhashEngine;
use League\Flysystem\Util;

class Blurhash {

    const defaultHash = 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay';
    public static $hashInfo = [
        'blurhash' => self::defaultHash,
        'width' => 0,
        'height' => 0,
    ];

	public static function generate($mediaPath, $mime)
	{
		if(!in_array($mime, ['image/png', 'image/jpeg', 'video/mp4'])) {
			return self::$hashInfo;
		}

		if($mediaPath == null) {
			return self::$hashInfo;
		}

        $imageData = '';
        if (!is_resource($mediaPath)) {
            $imageData = file_get_contents($mediaPath);
        } else {
            Util::rewindStream($mediaPath);
            while(!feof($mediaPath)){
                $imageData .= fgets($mediaPath, 4096);
            }
        }

        if (empty($imageData)) {
            return self::$hashInfo;
        }

		$image = imagecreatefromstring($imageData);
		if(!$image) {
			return self::$hashInfo;
		}
		$width = imagesx($image);
		$height = imagesy($image);

		$pixels = [];
		for ($y = 0; $y < $height; ++$y) {
			$row = [];
			for ($x = 0; $x < $width; ++$x) {
				$index = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $index);

				$row[] = [$colors['red'], $colors['green'], $colors['blue']];
			}
			$pixels[] = $row;
		}

		// Free the allocated GdImage object from memory:
		imagedestroy($image);

		$components_x = 4;
		$components_y = 4;
		$blurhash = BlurhashEngine::encode($pixels, $components_x, $components_y);
		if(strlen($blurhash) > 191) {
			return self::$hashInfo;
		}
		return compact('blurhash', 'width', 'height');
	}

	public static function generateV2($mediaPath)
	{
		if($mediaPath == null) {
			return self::$hashInfo;
		}

        $imageData = file_get_contents($mediaPath);
        if (empty($imageData)) {
            return self::$hashInfo;
        }

		$image = imagecreatefromstring($imageData);
		if(!$image) {
			return self::$hashInfo;
		}
		$width = imagesx($image);
		$height = imagesy($image);

		$pixels = [];
		for ($y = 0; $y < $height; ++$y) {
			$row = [];
			for ($x = 0; $x < $width; ++$x) {
				$index = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $index);

				$row[] = [$colors['red'], $colors['green'], $colors['blue']];
			}
			$pixels[] = $row;
		}

		// Free the allocated GdImage object from memory:
		imagedestroy($image);

		$components_x = 4;
		$components_y = 4;
		$blurhash = BlurhashEngine::encode($pixels, $components_x, $components_y);
		if(strlen($blurhash) > 191) {
			return self::$hashInfo;
		}
		return $blurhash;
	}
}
