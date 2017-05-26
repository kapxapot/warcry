<?php

namespace App\Validation\Rules;

use Warcry\Validation\Rules\TableFieldAvailable;

class GameNameAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('games', 'name', $id);
	}
}
