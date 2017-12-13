<?php

namespace App\Route\Generators\Entities;

use App\Route\Generators\EntityGenerator;

class Menus extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'link' => $this->rule('url'),
			'text' => $this->rule('text'),
			'position' => $this->rule('posInt'),
		];
	}
}
