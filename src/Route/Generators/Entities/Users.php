<?php

namespace App\Route\Generators\Entities;

use App\Route\Generators\EntityGenerator;

class Users extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'login' => $this->rule('login')->loginAvailable($id),
			'email' => $this->rule('url')->email()->emailAvailable($id),
			'password' => $this->rule('password', $id),
		];
	}
}
