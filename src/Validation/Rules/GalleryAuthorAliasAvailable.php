<?php

namespace App\Validation\Rules;

use Warcry\Validation\Rules\TableFieldAvailable;

class GalleryAuthorAliasAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('gallery_authors', 'alias', $id);
	}
}
