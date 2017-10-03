<?php

namespace App\Route\Generators;

class ComicStandalonePagesGenerator extends ComicPagesGeneratorBase {
	/*public function getRules($data, $id = null) {
		return [
			'link' => $this->rule('url'),
			'text' => $this->rule('text'),
			'position' => $this->rule('posInt'),
		];
	}*/
	
	public function getOptions() {
		//$container = $this->container;
		//$comics = $container->comics;
		
		return [
			'uri' => 'comic_standalones/{id:\d+}/comic_standalone_pages',
			'filter' => 'comic_standalone_id',
			
			// move to functions too?
			/*'mutator' => function($item) use ($comics) {
				$item['picture'] = $comics->getPictureUrl($item);
				$item['thumb'] = $comics->getThumbUrl($item);
				
				unset($item['type']);

				return $item;
			},*/
			
			//'admin_uri' => 'comic_standalones/{id:\d+}/comic_standalone_pages',
			/*'admin_mutator' => function($params, $args) use ($container) {
				$comicId = $args['id'];
				$comic = $container->db->getEntityById('comic_standalones', $comicId);
				$game = $container->db->getEntityById('games', $comic['game_id']);

				$params['source'] = "comic_standalones/{$comicId}/comic_standalone_pages";
				$params['breadcrumbs'] = [
					[ 'text' => 'Комиксы', 'link' => $container->router->pathFor('admin.comic_standalones') ],
					[ 'text' => $game ? $game['name'] : '(нет игры)' ],
					[ 'text' => $comic['name_ru'] ],
					[ 'text' => 'Страницы' ],
				];
				$params['hidden'] = [
					'comic_standalone_id' => $comicId,
				];
				
				return $params;
			},*/
		];
	}

	public function getAdminParams($params, $args) {
		$comicId = $args['id'];
		$comic = $this->db->getEntityById('comic_standalones', $comicId);
		$game = $this->db->getEntityById('games', $comic['game_id']);

		$params['source'] = "comic_standalones/{$comicId}/comic_standalone_pages";
		$params['breadcrumbs'] = [
			[ 'text' => 'Комиксы', 'link' => $this->router->pathFor('admin.comic_standalones') ],
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
