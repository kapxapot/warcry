<?php

namespace App\Controllers\Auth;

use Respect\Validation\Validator as v;

use Warcry\Util\Security;
use Warcry\Slim\Controllers\Controller;
use Warcry\Exceptions\NotFoundException;
use Warcry\Exceptions\ValidationException;
use Warcry\Exceptions\AuthenticationException;

use App\DB\Tables;

class AuthController extends Controller {
	public function postSignUp($request, $response) {
		$settings = $this->getSettings();
		
		$data = $request->getParsedBody();

		$userGen = $this->resolver->resolveEntity(Tables::USERS);

		$rules = $userGen->getRules($data);
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

		$user = $this->db->forTable(Tables::USERS)->create();
		$user->set($data);
		
		$password = $user->password;
		$user->password = Security::encodePassword($password);

		$user->save();

		// signing in
		$user = $this->auth->attempt($user->login, $password);
		
		$this->logger->info("User signed up: {$this->auth->userString()}");

		$token = $this->auth->getToken();

		$response = $response->withStatus(201);
		$response = $this->db->json($response, [ 'token' => $token->token, 'message' => 'Вы успешно зарегистрировались.' ]);

		return $response;
	}

	public function postSignIn($request, $response) {
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

		return $response;
	}

	public function postSignOut($request, $response) {
		$this->auth->logout();
		
		return $response;
	}
}
