<?php

namespace App\Handlers;

use Warcry\Contained;

class NotFoundHandler extends Contained {
	public function __invoke($request, $response) {
		$game = $this->db->getDefaultGame();
		return $this->view->render($response, 'main/generic.twig', [
			'menu' => $this->builder->buildMenu($game),
			'game' => $this->builder->buildGame($game),
			'text' => 'Страница не найдена или перемещена.',
			'title' => 'Ошибка 404',
			'no_disqus' => 1,
			'no_social' => 1,
		])->withStatus(404);
	}
}
