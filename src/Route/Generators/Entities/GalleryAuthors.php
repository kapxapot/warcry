<?php

namespace App\Route\Generators\Entities;

use App\Route\Generators\EntityGenerator;

class GalleryAuthors extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'name' => $this->rule('text')->galleryAuthorNameAvailable($id),
			'alias' => $this->rule('alias')->galleryAuthorAliasAvailable($id),
		];
	}
	
	public function getOptions() {
		return [
			'admin_uri' => 'gallery',
		];
	}
}
