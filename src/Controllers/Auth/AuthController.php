<?php

namespace App\Controllers\Auth;

use Warcry\Util\Util;

use Warcry\Slim\Controllers\Controller;

use Warcry\Exceptions\NotFoundException;
use Warcry\Exceptions\AuthenticationException;
use Warcry\Exceptions\ValidationException;

use Respect\Validation\Validator as v;

class AuthController extends Controller {
	public function postSignUp($request, $response) {
		$settings = $this->getSettings();
		
		try {
			$data = $request->getParsedBody();

			$rules = $this->validator->getRulesFor('users', $data);
			$validation = $this->validator->validate($request, $rules);
			
			if ($validation->failed()) {
				throw new ValidationException($validation->errors);
			}
			
			if (!$this->captcha->validate($data['captcha'])) {
				throw new AuthenticationException('Неверная или устаревшая капча.');
			}
			else {
				unset($data['captcha']);
			}

			$user = $this->db->forTable('users')->create();
			$user->set($data);
			
			$password = $user->password;
			$user->password = Util::encodePassword($password);

			$user->save();

			// signing in
			$user = $this->auth->attempt($user->login, $password);
			
			$this->logger->info("User signed up: {$this->auth->userString()}");

			$token = $this->auth->getToken();

			$response = $response->withStatus(201);
			$response = $this->db->json($response, [ 'token' => $token->token, 'message' => 'Вы успешно зарегистрировались.' ]);
		}
		catch (\Exception $ex) {
			$response = $this->db->error($response, $ex);
		}
		
		return $response;
	}

	public function postSignIn($request, $response) {
		try {
			$ok = $this->auth->attempt(
				$request->getParam('login'),
				$request->getParam('password')
			);
			
			if (!$ok) {
				throw new AuthenticationException('Пользователь с такими данными не найден.');
			}
			else {
				$this->logger->info("User logged in: {$this->auth->userString()}");
			
				$token = $this->auth->getToken();

				$response = $this->db->json($response, [ 'token' => $token->token, 'message' => 'Вы успешно вошли.' ]);
			}
		}
		catch (\Exception $ex) {
			$response = $this->db->error($response, $ex);
		}
		
		return $response;
	}
	
	public function getSignOut($request, $response) {
		$this->auth->logout();
		
		return $response->withRedirect($this->router->pathFor('admin.index'));
	}
}
