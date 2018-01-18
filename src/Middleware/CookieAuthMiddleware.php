<?php

namespace App\Middleware;

use Warcry\Slim\Middleware\Middleware;

class CookieAuthMiddleware extends Middleware {
	public function __invoke($request, $response, $next) {
        $token = $request->getCookieParam('auth_token');

		$this->auth->validateCookie($token);
		
		return $next($request, $response);
	}
}
