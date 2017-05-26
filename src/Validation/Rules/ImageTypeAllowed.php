<?php

namespace App\Validation\Rules;

use App\Gallery\Gallery;

use Warcry\File\Image;
use Warcry\Validation\Rules\ContainerRule;

class ImageTypeAllowed extends ContainerRule {
	public function validate($input) {
		$image = new Image();
		$image->parseBase64($input);

		return array_key_exists($image->imgType, Gallery::IMAGE_TYPES);
	}
}
