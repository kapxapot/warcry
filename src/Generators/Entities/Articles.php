<?php

namespace App\Route\Generators\Entities;

use App\Route\Generators\EntityGenerator;

class Articles extends EntityGenerator {
	public function getRules($data, $id = null) {
		$rules = [
			'name_ru' => $this->rule('text'),//->regex($cyr("'\(\):\-\.\|,\?!—«»"));
		];

		if (array_key_exists('name_en', $data) && array_key_exists('cat', $data)) {
			$rules['name_en'] = $this->rule('text')
				->regex($this->rules->lat("':\-"))
				->articleNameCatAvailable($data['cat'], $id);
		}
		
		return $rules;
	}
	
	public function getOptions() {
		return [
			'exclude' => [ 'text' ],
			'admin_template' => 'article',
		];
	}
	
	public function beforeSave($data, $id = null) {
		$data['cache'] = null;
		$data['contents_cache'] = null;
		
		return $data;
	}
}
