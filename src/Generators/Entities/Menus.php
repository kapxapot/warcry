<?php

namespace App\Generators\Entities;

use App\Generators\EntityGenerator;

class Menus extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'link' => $this->rule('url'),
			'text' => $this->rule('text'),
			'position' => $this->rule('posInt'),
		];
	}
}
