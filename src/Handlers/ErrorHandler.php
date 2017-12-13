<?php

namespace App\Handlers;

use Warcry\Contained;

class ErrorHandler extends Contained {
	public function __invoke($request, $response, $exception) {
    	return $this->db->error($response, $exception);
	}
}
