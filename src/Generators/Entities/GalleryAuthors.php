<?php

namespace App\Generators\Entities;

use App\Generators\EntityGenerator;

class GalleryAuthors extends EntityGenerator {
	public function getRules($data, $id = null) {
		$rules = [
			'name' => $this->rule('text')->galleryAuthorNameAvailable($id),
		];
		
		if (array_key_exists('alias', $data)) {
			$rules['alias'] = $this->rule('alias')->galleryAuthorAliasAvailable($id);
		}
		
		return $rules;
	}
	
	public function getOptions() {
		return [
			'admin_uri' => 'gallery',
		];
	}
}
