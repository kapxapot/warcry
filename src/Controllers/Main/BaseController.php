<?php

namespace App\Controllers\Main;

use Illuminate\Support\Arr;
use Warcry\Slim\Controllers\Controller;

class BaseController extends Controller {
	protected function buildParams($settings) {
		$games = $this->db->getGames();
		$game = $settings['game'] ?? $this->db->getDefaultGame();
		
		$params = [
			'games' => $this->builder->buildGames($games),
			'game' => $this->builder->buildGame($game),
			'menu' => $this->builder->buildMenu($game),
		];

		$sidebar = $this->buildSidebar($settings);
		
		if (count($sidebar) > 0) {
			$params['sidebar'] = $sidebar;
		}
		else {
			$params['one_column'] = 1;
		}

		return array_merge($params, $settings['params']);
	}
	
	private function buildSidebar($settings) {
		$result = [];
		
		if (is_array($settings['sidebar'])) {
			$game = $settings['game'];
			
			foreach ($settings['sidebar'] as $part) {
				switch ($part) {
					case 'news':
						$limit = $this->getSettings('legacy.sidebar.latest_news_limit');
						$exceptNewsId = $settings['news_id'] ?? null;
						
						$result[$part] = $this->builder->buildLatestNews($game, $limit, $exceptNewsId);
						break;
					
					case 'forum':
						$limit = $this->getSettings('legacy.sidebar.forum_topic_limit');
						$result[$part] = $this->builder->buildForumTopics($game, $limit);
						break;
					
					case 'articles':
						$limit = $this->getSettings('legacy.sidebar.article_limit');
						$exceptArticleId = $settings['article_id'] ?? null;
						
						$result[$part] = $this->builder->buildLatestArticles($game, $limit, $exceptArticleId);
						break;
					
					case 'stream':
						$result[$part] = $this->builder->buildOnlineStream($game);
						break;

					default:
						$bits = explode('.', $part);
						if (count($bits) > 1) {
							$action = $bits[0];
							$entity = $bits[1];

							Arr::set($result, "actions.{$action}.{$entity}", true);
						}
						else {
							throw new \InvalidArgumentException('Unknown sidebar part: ' . $part);
						}
				}
			}
		}

		return $result;
	}
	
	protected function notFound($request, $response) {
		$handler = $this->notFoundHandler;
		return $handler($request, $response);
	}
}
