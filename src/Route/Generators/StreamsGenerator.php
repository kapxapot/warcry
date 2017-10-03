<?php

namespace App\Route\Generators;

class StreamsGenerator extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'title' => $this->rule('text')->streamTitleAvailable($id),
			'stream_id' => $this->rule('extendedAlias')->streamIdAvailable($id),
			'description' => $this->rule('text'),
		];
	}
}
