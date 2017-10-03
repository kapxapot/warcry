<?php

namespace App\Route\Generators;

class ComicIssuesGenerator extends EntityGenerator {
	/*public function getRules($data, $id = null) {
		return [
			'link' => $this->rule('url'),
			'text' => $this->rule('text'),
			'position' => $this->rule('posInt'),
		];
	}*/
	
	public function getOptions() {
		//$container = $this->container;
		
		return [
			'uri' => 'comic_series/{id:\d+}/comic_issues',
			'filter' => 'series_id',
			
			//'admin_uri' => 'comic_series/{id:\d+}/comic_issues',
			/*'admin_mutator' => function($params, $args) use ($container) {
				$seriesId = $args['id'];
				$series = $container->db->getEntityById('comic_series', $seriesId);
				$game = $container->db->getEntityById('games', $series['game_id']);
				
				$params['source'] = "comic_series/{$seriesId}/comic_issues";
				$params['breadcrumbs'] = [
					[ 'text' => 'Серии комиксов', 'link' => $container->router->pathFor('admin.comic_series') ],
					[ 'text' => $game ? $game['name'] : '(нет игры)' ],
					[ 'text' => $series['name_ru'] ],
					[ 'text' => 'Комиксы' ],
				];
				$params['hidden'] = [
					'series_id' => $seriesId,
				];
				
				return $params;
			},*/
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
