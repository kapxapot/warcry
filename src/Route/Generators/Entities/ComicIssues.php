<?php

namespace App\Route\Generators\Entities;

use App\Route\Generators\EntityGenerator;

class ComicIssues extends EntityGenerator {
	public function getOptions() {
		return [
			'uri' => 'comic_series/{id:\d+}/comic_issues',
			'filter' => 'series_id',
		];
	}
	
	public function getAdminParams($params, $args) {
		$seriesId = $args['id'];
		$series = $this->db->getEntityById('comic_series', $seriesId);
		$game = $this->db->getEntityById('games', $series['game_id']);
		
		$params['source'] = "comic_series/{$seriesId}/comic_issues";
		$params['breadcrumbs'] = [
			[ 'text' => 'Серии', 'link' => $this->router->pathFor('admin.comic_series') ],
			[ 'text' => $game ? $game['name'] : '(нет игры)' ],
			[ 'text' => $series['name_ru'] ],
			[ 'text' => 'Комиксы' ],
		];
		
		$params['hidden'] = [
			'series_id' => $seriesId,
		];
		
		return $params;
	}
}
