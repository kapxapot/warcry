<?php

namespace App\Validation\Rules;

use Warcry\Validation\Rules\TableFieldAvailable;

class StreamTitleAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('streams', 'title', $id);
	}
}
