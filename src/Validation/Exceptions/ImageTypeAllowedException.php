<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class ImageTypeAllowedException extends ValidationException {
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Неверный тип изображения.'
		]
	];
}
