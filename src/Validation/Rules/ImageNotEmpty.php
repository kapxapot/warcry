<?php

namespace App\Validation\Rules;

use Warcry\File\Image;
use Warcry\Validation\Rules\ContainerRule;

class ImageNotEmpty extends ContainerRule {
	public function validate($input) {
		$image = Image::parseBase64($input);
		
		return $image->notEmpty();
	}
}
