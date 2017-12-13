<?php

namespace App\Middleware;

use Warcry\Slim\Middleware\Middleware;

class HomeMiddleware extends Middleware {
	protected $homePath;

	public function __construct($container, $home) {
		parent::__construct($container);
		
		$this->homePath = $this->router->pathFor($home);
	}
}
