<?php

namespace App\Controllers\Auth;

use Respect\Validation\Validator as v;

use Warcry\Util\Util;
use Warcry\Slim\Controllers\Controller;
use Warcry\Exceptions\ValidationException;

use App\Validation\ValidationRules;

class PasswordController extends Controller {
	public function postChangePassword($request, $response) {
		$user = $this->auth->getUser();

		$data = [ 'password' => $user->password ];
		$rules = $this->getRules($data);
		$validation = $this->validator->validate($request, $rules);
		
		if ($validation->failed()) {
			throw new ValidationException($validation->errors);
		}
		
		$password = $request->getParam('password');
		
		$user->set('password', Util::encodePassword($password));
		$user->save();
		
		$this->logger->info("Changed password for user: {$this->auth->userString()}");

		$response = $this->db->json($response, [ 'message' => 'Пароль успешно изменен.' ]);

		return $response;
	}
	
	private function getRules($data) {
		$rules = new ValidationRules($this->container);

		return [
			'password_old' => v::matchesPassword($data['password']),
			'password' => $rules->get('password'),
		];
	}
}
