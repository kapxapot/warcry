<?php

namespace App\Gallery;

use Warcry\File\Image;

class Comics extends GalleryBase {
	private $thumbHeight = 400;
	
	public function __construct($container) {
		$settings = [
			'base_dir' => __DIR__,
			'fields' => [
				'picture_type' => 'type',
				'thumb_type' => 'type',
			],
			'folders' => [
				'picture' => [
					'storage' => 'comics_pages',
					'public' => 'comics_pages_public',
				],
				'thumb' => [
					'storage' => 'comics_thumbs',
					'public' => 'comics_thumbs_public',
				],
			],
		];
		
		parent::__construct($container, $settings);
	}
	
	protected function getThumb($data) {
		$thumb = null;
		
		$picture = $this->getPicture($data);
		if ($picture && $picture->notEmpty()) {
			$data = $picture->data;
			
			$image = imagecreatefromstring($data);
			if ($image === false) {
				throw new \InvalidArgumentException('Error parsing comic page image');
			}

			list($width, $height) = getimagesizefromstring($data);
			
			$newHeight = $this->thumbHeight;
			$newWidth = $width * $newHeight / $height;

			$thumbImage = imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($thumbImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
			
			$thumb = new Image;
			$thumb->data = $this->gdImgToBase64($thumbImage, $picture->imgType);
			$thumb->imgType = $picture->imgType;

			imagedestroy($thumbImage);
			imagedestroy($image);
		}
		
		return $thumb;
	}
	
	private function gdImgToBase64($gdImg, $format = 'jpeg') {
		$data = null;
		
	    if (array_key_exists($format, GalleryBase::IMAGE_TYPES)) {
	        ob_start();
	
	        if ($format == 'jpeg' ) {
	            imagejpeg($gdImg);
	        } elseif ($format == 'png') {
	            imagepng($gdImg);
	        } elseif ($format == 'gif') {
	            imagegif($gdImg);
	        }
	
	        $data = ob_get_contents();

	        ob_end_clean();
	    }
	
	    return $data;
	}
}
