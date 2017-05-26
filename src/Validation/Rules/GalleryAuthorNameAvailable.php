<?php

namespace App\Validation\Rules;

use Warcry\Validation\Rules\TableFieldAvailable;

class GalleryAuthorNameAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('gallery_authors', 'name', $id);
	}
}
