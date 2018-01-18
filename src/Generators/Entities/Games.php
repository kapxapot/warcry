<?php

namespace App\Generators\Entities;

use App\Generators\EntityGenerator;

class Games extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'icon' => $this->rule('url'),
			'name' => $this->rule('text')->gameNameAvailable($id),
			'alias' => $this->rule('alias')->gameAliasAvailable($id),
			'news_forum_id' => $this->optional('posInt'),
			'main_forum_id' => $this->optional('posInt'),
			'position' => $this->rule('posInt'),
		];
	}
}
