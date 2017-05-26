<?php

namespace App\Middleware;

use Warcry\Slim\Middleware\Middleware;

class TokenAuthMiddleware extends Middleware {
	public function __invoke($request, $response, $next) {
		try {
	        $token = $request->getHeaderLine('Authorization');
			if ($this->auth->validateToken($token)) {
				$response = $next($request, $response);
			}
		}
		catch (\Exception $ex) {
			$this->db->error($response, $ex);
		}
				
		return $response;
	}
}
