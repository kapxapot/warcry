<?php

namespace App\Generators\Entities;

use App\Generators\EntityGenerator;

class ComicIssues extends EntityGenerator {
	public function getOptions() {
		return [
			'uri' => 'comic_series/{id:\d+}/comic_issues',
			'filter' => 'series_id',
		];
	}
	
	public function afterLoad($item) {
		$series = $this->db->getComicSeries($item['series_id']);
		if ($series) {
			$item['series_alias'] = $series['alias'];
		}

		return $item;
	}
	
	public function getAdminParams($args) {
		$params = parent::getAdminParams($args);
		
		$seriesId = $args['id'];
		$series = $this->db->getEntityById('comic_series', $seriesId);
		$game = $this->db->getEntityById('games', $series['game_id']);
		
		$params['source'] = "comic_series/{$seriesId}/comic_issues";
		$params['breadcrumbs'] = [
			[ 'text' => 'Серии', 'link' => $this->router->pathFor('admin.entities.comic_series') ],
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
