<?php

namespace App\Controllers\Auth;

use Warcry\Util\Util;
use Warcry\Slim\Controllers\Controller;
use Warcry\Exceptions\ValidationException;

use Respect\Validation\Validator as v;

class PasswordController extends Controller {
	public function postChangePassword($request, $response) {
		$settings = $this->getSettings();
		
		try {
			$user = $this->auth->getUser();

			$data = [ 'password' => $user->password ];
			$rules = $this->validator->getRulesFor('password', $data);
			$validation = $this->validator->validate($request, $rules);
			
			if ($validation->failed()) {
				throw new ValidationException($validation->errors);
			}
			
			$password = $request->getParam('password');
			
			$user->set('password', Util::encodePassword($password));
			$user->save();
			
			$this->logger->info("Changed password for user: {$this->auth->userString()}");

			$response = $this->db->json($response, [ 'message' => 'Пароль успешно изменен.' ]);
		}
		catch (\Exception $ex) {
			$response = $this->db->error($response, $ex);
		}
		
		return $response;
	}
}
