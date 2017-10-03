<?php

namespace App\Route\Generators;

class ComicPagesGenerator extends ComicPagesGeneratorBase {
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
			//'uri' => 'comic_series/{series_id:\d+}/comic_issues/{id:\d+}/comic_pages',
			'uri' => 'comic_issues/{id:\d+}/comic_pages',
			'filter' => 'comic_issue_id',
			
			//'admin_uri' => 'comic_series/{series_id:\d+}/comic_issues/{id:\d+}/comic_pages',
			/*'admin_mutator' => function($params, $args) use ($container) {
				$seriesId = $args['series_id'];
				$comicId = $args['id'];
				$series = $container->db->getEntityById('comic_series', $seriesId);
				$game = $container->db->getEntityById('games', $series['game_id']);
				$comic = $container->db->getEntityById('comic_issues', $comicId);
				
				$params['source'] = "comic_series/{$seriesId}/comic_issues/{$comicId}/comic_pages";
				$params['breadcrumbs'] = [
					[ 'text' => 'Серии комиксов', 'link' => $container->router->pathFor('admin.comic_series') ],
					[ 'text' => $game ? $game['name'] : '(нет игры)' ],
					[ 'text' => $series['name_ru'], 'link' => $container->router->pathFor('admin.comic_issues', [ 'id' => $seriesId ]) ],
					[ 'text' => $comic['number'] ],
					[ 'text' => 'Страницы' ],
				];
				$params['hidden'] = [
					'comic_issue_id' => $comicId,
				];
				
				return $params;
			},*/
		];
	}
	
	public function getAdminParams($params, $args) {
		$comicId = $args['id'];
		$comic = $this->db->getEntityById('comic_issues', $comicId);
		$seriesId = $comic['series_id'];
		$series = $this->db->getEntityById('comic_series', $seriesId);
		$game = $this->db->getEntityById('games', $series['game_id']);

		//$params['source'] = "comic_series/{$seriesId}/comic_issues/{$comicId}/comic_pages";
		$params['source'] = "comic_issues/{$comicId}/comic_pages";
		$params['breadcrumbs'] = [
			[ 'text' => 'Серии', 'link' => $this->router->pathFor('admin.comic_series') ],
			[ 'text' => $game ? $game['name'] : '(нет игры)' ],
			[ 'text' => $series['name_ru'], 'link' => $this->router->pathFor('admin.comic_issues', [ 'id' => $seriesId ]) ],
			[ 'text' => '#' . $comic['number'] . ($comic['name_ru'] ? ': ' . $comic['name_ru'] : '') ],
			[ 'text' => 'Страницы' ],
		];
		
		$params['hidden'] = [
			'comic_issue_id' => $comicId,
		];
		
		return $params;
	}
}
