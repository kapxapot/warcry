<?php

namespace App\Validation\Rules;

use Warcry\File\Image;
use Warcry\Validation\Rules\ContainerRule;

use App\Gallery\GalleryBase;

class ImageTypeAllowed extends ContainerRule {
	public function validate($input) {
		$image = Image::parseBase64($input);

		return array_key_exists($image->imgType, GalleryBase::IMAGE_TYPES);
	}
}
