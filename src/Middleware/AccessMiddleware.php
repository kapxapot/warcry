<?php

namespace App\Middleware;

use Warcry\Slim\Middleware\Middleware;
use Warcry\Exceptions\AuthenticationException;

class AccessMiddleware extends Middleware {
	private $entity;
	private $action;
	private $redirect;
	
	public function __construct($container, $entity, $action, $redirect = null) {
		parent::__construct($container);
		
		$this->entity = $entity;
		$this->action = $action;
		$this->redirect = $redirect;
	}
	
	public function __invoke($request, $response, $next) {
		try {
			$access = $this->container->access;
			if ($access->checkRights($this->entity, $this->action)) {
				$response = $next($request, $response);
			}
			elseif ($this->redirect) {
				return $response->withRedirect($this->router->pathFor($this->redirect));
			}
			else {
				throw new AuthenticationException();
			}
		}
		catch (\Exception $ex) {
			$this->db->error($response, $ex);
		}
				
		return $response;
	}
}
