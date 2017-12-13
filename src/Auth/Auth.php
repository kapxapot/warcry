<?php

namespace App\Auth;

use Warcry\Util\Util;
use Warcry\Contained;
use Warcry\Exceptions\AuthenticationException;

use App\DB\Tables;

class Auth extends Contained {
	private $user;
	private $role;
	private $token;

	private function setUser($user) {
		$_SESSION['user'] = $user->id;
		$this->user = null;
	}
	
	private function setToken($token) {
		$_SESSION['token'] = $token->id;
		$this->token = null;
	}
	
	private function login($user, $token) {
		$this->setUser($user);
		$this->setToken($token);
	}
	
	public function getUser() {
		if (!$this->user) {
			$id = $_SESSION['user'];
			if ($id != null) {
				$user = $this->db->forTable(Tables::USERS)->findOne($id);
				if (empty($user->name)) {
					$user->name = $user->login;
				}
			
				$this->user = $user;
			}
		}
		
		return $this->user;
	}
	
	public function getRole() {
		if (!$this->role) {
			$user = $this->getUser();
			if ($user) {
				$id = $user->role_id;
				$this->role = $this->db->forTable(Tables::ROLES)->findOne($id);
			}
		}
		
		return $this->role;
	}
	
	public function getToken() {
		if (!$this->token) {
			$id = $_SESSION['token'];
			if ($id != null) {
				$this->token = $this->db->forTable(Tables::AUTH_TOKENS)->findOne($id);
			}
		}
		
		return $this->token;
	}
	
	public function userString() {
		$user = $this->getUser();
		return ($user != null)
			? "[{$user->id}] {$user->name}"
			: null;
	}
	
	public function tokenString() {
		$token = $this->getToken();
		return ($token != null)
			? "{$token->token}, expires at {$token->expires_at}"
			: null;
	}

	public function check() {
		return $this->getUser();
	}
	
	public function attempt($login, $password) {
		$user = $this->db
			->forTable(Tables::USERS)
			->whereAnyIs([
                [ 'login' => $login ],
                [ 'email' => $login ],
            ])
			->findOne();
		
		$ok = false;

		if ($user) {
			if (Util::verifyPassword($password, $user->password)) {
				if (Util::rehashPasswordNeeded($user->password)) {
					$user->password = Util::encodePassword($password);
					$user->save();
				}
				
				$token = $this->db->forTable(Tables::AUTH_TOKENS)->create();
				$token->user_id = $user->id;
				$token->token = Util::generateToken();
				$token->expires_at = $this->generateExpirationTime();
				
				$token->save();

				$this->login($user, $token);

				$ok = true;
			}
		}
		
		return $ok;
	}

	public function logout() {
		unset($_SESSION['token']);
		unset($_SESSION['user']);
	}
	
	private function generateExpirationTime() {
		$ttl = $this->getSettings('token_ttl');
		return Util::generateExpirationTime($ttl * 60);
	}

	public function validateToken($tokenStr) {
		$token = $this->getToken();
		if (!$token || $token->token != $tokenStr) {
			$token = $this->db->forTable(Tables::AUTH_TOKENS)
				->where('token', $tokenStr)
				->findOne();
			
			if ($token == null) {
				throw new AuthenticationException('Неверный токен безопасности.');
			}
			elseif (strtotime($token['expires_at']) < time()) {
				throw new AuthenticationException('Истек срок действия токена безопасности.');
			}
		}
			
		$token->expires_at = $this->generateExpirationTime();
		
		$token->save();

		$this->setToken($token);

		return $token;
	}
	
	public function isOwnerOf($item) {
		$user = $this->getUser();
		return isset($item['created_by']) && ($user !== null) && ($item['created_by'] == $user->id);
	}
}
