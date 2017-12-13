<?php

namespace App\Handlers;

use Warcry\Contained;
use Warcry\Exceptions\AuthenticationException;

class NotAllowedHandler extends Contained {
	public function __invoke($request, $response) {
    	$ex = new AuthenticationException('Method not allowed.');
    	return $this->db->error($response, $ex);
	}
}
