<?php

namespace App\Middleware;

use Warcry\Slim\Middleware\Middleware;
use Warcry\Exceptions\AuthenticationException;

class TokenAuthMiddleware extends Middleware {
	public function __invoke($request, $response, $next) {
        $tokenLine = $request->getHeaderLine('Authorization');
        $lineParts = explode(' ', $tokenLine);
        
        if (count($lineParts) < 2) {
			throw new AuthenticationException('Invalid authorization header format. Expected "Bearer <token>".');
        }
        
        $token = $lineParts[1];

		if ($this->auth->validateToken($token)) {
			$response = $next($request, $response);
		}

		return $response;
	}
}
