<?php

namespace App\Route\Generators\Entities;

use Warcry\Util\Util;

use App\Route\Generators\EntityGenerator;

class News extends EntityGenerator {
	public function beforeSave($data, $id = null) {
		$data['cache'] = null;
		
		if (isset($data['published']) && $data['published'] == 1 && !isset($data['published_at'])) {
			$data['published_at'] = Util::now();
		}

		return $data;
	}
}
