<?php

namespace App\Generators\Entities;

class ComicStandalonePages extends ComicPagesBase {
	public function getOptions() {
		return [
			'uri' => 'comic_standalones/{id:\d+}/comic_standalone_pages',
			'filter' => 'comic_standalone_id',
		];
	}

	public function getAdminParams($args) {
		$params = parent::getAdminParams($args);

		$comicId = $args['id'];
		$comic = $this->db->getEntityById('comic_standalones', $comicId);
		$game = $this->db->getEntityById('games', $comic['game_id']);

		$params['source'] = "comic_standalones/{$comicId}/comic_standalone_pages";
		$params['breadcrumbs'] = [
			[ 'text' => 'Комиксы', 'link' => $this->router->pathFor('admin.entities.comic_standalones') ],
			[ 'text' => $game ? $game['name'] : '(нет игры)' ],
			[ 'text' => $comic['name_ru'] ],
			[ 'text' => 'Страницы' ],
		];
		
		$params['hidden'] = [
			'comic_standalone_id' => $comicId,
		];
		
		return $params;
	}
}
