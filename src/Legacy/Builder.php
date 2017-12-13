<?php

namespace App\Legacy;

use Warcry\Contained;
use Warcry\Util\Cases;
use Warcry\Util\Sort;

class Builder extends Contained {
	private $router;
	private $decorator;
	private $newsParser;
	private $forumParser;

	public function __construct($container) {
		parent::__construct($container);
		
		$this->router = $this->legacyRouter; // from container
		$this->decorator = $this->legacyDecorator; // from container
		$this->newsParser = $this->legacyNewsParser; // from container
		$this->forumParser = $this->legacyForumParser; // from container
	}

	public function buildGame($row) {
		$game = $row;
		
		$game['default'] = ($game['id'] == $this->db->getDefaultGameId());
		$game['url'] = $this->router->game($game);

		return $game;
	}
	
	public function buildGames($rows) {
		return array_map(function($row) {
			return $this->buildGame($row);
		}, $rows);
	}
	
	private function formatDate($date) {
		return strftime($this->getSettings('legacy.date_format'), $date);
	}
	
	private function formatDateTime($date) {
		return strftime($this->getSettings('legacy.time_format'), $date);
	}

	public function buildForumNews($news, $full = false) {
		$id = $news['tid'];
		
		$news['id'] = $id;
		$news['title'] = $this->newsParser->decodeTopicTitle($news['title']);

		$post = $this->newsParser->beforeParsePost($news['post'], $id, $full);
		$post = $this->forumParser->convert([ 'TEXT' => $post, 'CODE' => 1 ]);
		$news['text'] = $this->newsParser->afterParsePost($post);

		$game = $this->db->getGameByForumId($news['forum_id']);
		$news['game'] = $this->buildGame($game);

		$tagRows = $this->db->getForumTopicTags($id);

		if ($tagRows) {
			foreach ($tagRows as $tagRow) {
				$text = $tagRow['tag_text'];
				$tags[] = [ 'text' => $text, 'url' => $this->router->forumTag($text) ];
			}
		}

		$news['pub_date'] = $news['start_date'];
		$news['start_date'] = $this->formatDateTime($news['start_date']);
		$news['starter_url'] = $this->router->forumUser($news['starter_id']);
		$news['tags'] = $tags;
		$news['url'] = $this->router->news($id);
		$news['forum_url'] = $this->router->forumTopic($id);
		
		$news['description'] = substr(strip_tags($news['text']), 0, 1000);
		
		return $news;
	}

	public function buildNews($news, $full = false, $rebuild = false) {
		$id = $news['id'];
		
		$game = $this->db->getGame($news['game_id']);
		$news['game'] = $this->buildGame($game);

		if (!$rebuild && strlen($news['cache']) > 0) {
			$text = $news['cache'];
		}
		else {
			$parsed = $this->legacyArticleParser->parseBB($news['text']);
			$text = $parsed['text'];

			$this->db->saveNewsCache($id, $text);
		}
		
		$text = $this->legacyNewsParser->parseCut($id, $text, $full);
		$text = str_replace('%article%/', $this->router->article(), $text);

		$news['text'] = $text;

		if (strlen($text) > 0) {
			$news['description'] = substr(strip_tags($text), 0, 1000);
		}
		
		$news['tags'] = array_map(function($t) {
			return [
				'text' => trim($t),
				//'url' => $this->router->forumTag($text),
			];
		}, explode(',', $news['tags']));

		$news = $this->stamps($news);

		$news['pub_date'] = strtotime($news['published_at']);
		$news['start_date'] = $news['published_at']
			? $this->formatDateTime(strtotime($news['published_at']))
			: 'Не опубликована!';

		$news['starter_name'] = $news['author']['name'];
		$news['starter_url'] = $news['author']['member_url'];
		$news['url'] = $this->router->news($id);

		return $news;
	}

	public function buildForumNewsLink($row) {
		$id = $row['tid'];
		
		$title = $this->newsParser->decodeTopicTitle($row['title']);
		$game = $this->db->getGameByForumId($row['forum_id']);

		return [
			'title' => $title,
			'game' => $game,
			'posts' => $row['posts'],
			'url' => $this->router->news($id),
		];
	}
	
	public function buildForumTopic($filterByGame, $row) {
		$title = $this->newsParser->decodeTopicTitle($row['title']);

		return [
			'title' => $this->newsParser->decodeTopicTitle($row['title']),
			'url' => $this->router->forumTopic($row['tid'], true),
			'game' => $filterByGame ?? $this->db->getGameByForumId($row['forum_id']),
			'posts' => $row['posts'],
		];
	}
	
	public function buildForumTopics($filterByGame, $limit) {
		$rows = $this->db->getLatestForumTopics($filterByGame, $limit);

		return array_map(function($row) {
			return $this->buildForumTopic($filterByGame, $row);
		}, $rows);
	}
	
	public function buildLatestNews($filterByGame, $limit, $exceptNewsId) {
		$rows = $this->buildAllNews($filterByGame, 0, $limit, $exceptNewsId);
		
		return array_map(function($row) {
			return $this->buildForumNewsLink($row);
		}, $rows);
	}
	
	public function buildAllNews($filterByGame, $offset, $limit, $exceptNewsId = null) {
		$forumNewsRows = $this->db->getLatestForumNews($filterByGame, $offset, $limit, $exceptNewsId);
		foreach ($forumNewsRows as $row) {
			$news[] = $this->buildForumNews($row);
		}
		
		$newsRows = $this->db->getLatestNews($filterByGame, $offset, $limit, $exceptNewsId);
		foreach ($newsRows as $row) {
			$news[] = $this->buildNews($row);
		}
		
		$sorts = [
			'pub_date' => [ 'dir' => 'desc' ],
		];

		$sort = new Sort;
		$sorted = $sort->multiSort($news, $sorts);
		$news = array_slice($sorted, 0, $limit);
		
		return $news;
	}
	
	public function buildArticle($article) {
		$result = $article->data;

		if ($result['cat']) {
			$result['cat'] = $this->db->getCat($result['cat']);
		}
		
		$result['game'] = $this->db->getGame($result['game_id']);

		$text = str_replace('%article%/', $this->router->article(), $article->text);

		if (strlen($text) > 0) {
			$result['description'] = substr(strip_tags($text), 0, 2000);
		}

		$subArticleRows = $this->db->getSubArticles($article->id);
		if ($subArticleRows) {
			foreach ($subArticleRows as $saRow) {
				$subList[] = $this->buildArticleLink($saRow);
			}
		
			$result['sub_articles'] = $subList;
		}

		$result['breadcrumbs'] = $article->breadcrumbs;
		$result['contents'] = $article->contents;

		$link = $this->buildArticleLink($result);
		$result['title'] = $link['title_full'];

		$result['text'] = $text;

		return $this->stamps($result);
	}

	private function getItem($id) {
		$item = $this->db->getItem($id);
		
		if (!$item || !isset($item['name_ru'])) {
			$item = $this->getRemoteItem($id);
		}

		return $item;
	}

	private function getRemoteItem($id) {
		$url = $this->router->wowheadItemXml($id);
		$urlRu = $this->router->wowheadItemRuXml($id);
		
		$xml = simplexml_load_file($url, null, LIBXML_NOCDATA);
		$xmlRu = simplexml_load_file($urlRu, null, LIBXML_NOCDATA);
		
		if ($xml !== false) {
			$name = (string)$xml->item->name;
			
			$item = [
				'icon' => (string)$xml->item->icon,
				'name' => $name,
				'quality' => (string)$xml->item->quality['id'],
			];
		
			if ($xmlRu !== false) {
				$nameRu = (string)$xmlRu->item->name;
				
				if ($nameRu !== $name) {
					$item['name_ru'] = $nameRu;
				}
			}

			$this->db->saveItem($id, $item);
		}
		
		return $item;
	}

	protected function getSpellIcon($id) {
		$icon = $this->db->getSpellIcon($id);
		return ($icon != null) ? $icon['icon'] : null;
	}
	
	protected function invertQuality($q) {
		return 8 - $q;
	}
	
	private function extractRecipeReagents($reagentsString) {
		$reagents = [];
		
		if (strlen($reagentsString) > 0) {
			$reagentsRaw = explode(',', $reagentsString);
			
			foreach ($reagentsRaw as $reagent) {
				$parts = explode('x', $reagent);
				list($id, $count) = $parts;
				
				$reagents[$id] = $count;
			}
		}
		
		return $reagents;
	}
	
	private function getRecipeBaseReagents($recipe, $baseReagents = []) {
		foreach ($recipe['reagents'] as $reagent) {
			if (isset($reagent['recipe'])) {
				$baseReagents = $this->getRecipeBaseReagents($reagent['recipe'], $baseReagents);
			}
			else {
				$id = $reagent['item_id'];
			
				if (!isset($baseReagents[$id])) {
					$baseReagents[$id] = $reagent;
				}
				else {
					$baseReagents[$id]['total_min'] += $reagent['total_min'];
					$baseReagents[$id]['total_max'] += $reagent['total_max'];
				}
			}
		}
		
		return $baseReagents;
	}
	
	private function addNodeIds($recipe, $label = '1') {
		$recipe['node_id'] = $label;

		$count = 1;
		foreach ($recipe['reagents'] as &$reagent) {
			if (isset($reagent['recipe'])) {
				$reagent['recipe'] = $this->addNodeIds($reagent['recipe'], $label . '_' . $count++);
			}
		}
		
		return $recipe;
	}
	
	private function addTotals($recipe, $countMin = 1, $countMax = 1) {
		$createsMin = $recipe['creates_min'];
		$createsMax = $recipe['creates_max'];
		
		$neededMin = ($createsMax > 0) ? ceil($countMin / $createsMax) : 0;
		$neededMax = ($createsMin > 0) ? ceil($countMax / $createsMin) : 0;

		$recipe['total_min'] = $neededMin;
		$recipe['total_max'] = $neededMax;

		foreach ($recipe['reagents'] as &$reagent) {
			$count = $reagent['count'];

			$totalMin = ($neededMin > 0) ? $neededMin * $count : $count;
			$totalMax = ($neededMax > 0) ? $neededMax * $count : $count;

			$reagent['total_min'] = $totalMin;
			$reagent['total_max'] = $totalMax;

			if (isset($reagent['recipe'])) {
				$reagent['recipe'] = $this->addTotals($reagent['recipe'], $totalMin, $totalMax);
			}
		}
		
		return $recipe;
	}
	
	public function buildRecipe($recipe, $cacheEnabled = true, &$requiredSkills = [], $trunk = []) {
		$topLevel = empty($trunk);

		// на всякий -__-
		if (count($trunk) > 20) {
			return;
		}

		$trunk[] = $recipe['creates_id'];

		/*$createsMin = $recipe['creates_min'];
		$createsMax = $recipe['creates_max'];
		
		$neededMin = ($createsMax > 0) ? ceil($countMin / $createsMax) : 0;
		$neededMax = ($createsMin > 0) ? ceil($countMax / $createsMin) : 0;

		$recipe['total_min'] = $neededMin;
		$recipe['total_max'] = $neededMax;*/
		
		// title
		$result['title'] = $result['name_ru'];
		if ($result['name'] && $result['name'] != $result['name_ru']) {
			$result['title'] .= ' (' . $result['name'] . ')';
		}

		// learned at
		if ($recipe['learnedat'] == 9999) {
			$recipe['learnedat'] = '??';
		}

		// lvls
		$recipe['levels'] = [
			'orange' => $recipe['lvl_orange'],
			'yellow' => $recipe['lvl_yellow'],
			'green' => $recipe['lvl_green'],
			'gray' => $recipe['lvl_gray'],
		];

		// skill
		$skillId = $recipe['skill'];
		$recipe['skill_id'] = $skillId;
		$recipe['skill'] = $this->db->getSkill($skillId);
		
		if (!isset($requiredSkills[$skillId])) {
			$requiredSkills[$skillId] = [
				'skill' => $recipe['skill'],
				'max' => $recipe['learnedat'],
			];
		}
		else {
			$curMax = $requiredSkills[$skillId]['max'];
			
			if ($recipe['learnedat'] > $curMax) {
				$requiredSkills[$skillId]['max'] = $recipe['learnedat'];
			}
		}

		// source
		$srcIds = explode(',', $recipe['source']);
		$sources = array_map(function($srcId) {
			$src = $this->db->getRecipeSource($srcId);
			return $src ? $src['name_ru'] : $srcId;
		}, $srcIds);

		// reagents
		if ($cacheEnabled && strlen($recipe['reagent_cache']) > 0) {
			$reagents = json_decode($recipe['reagent_cache'], true);
		}
		else {
			$reagents = [];

			$extRegs = $this->extractRecipeReagents($recipe['reagents']);
			
			foreach ($extRegs as $id => $count) {
				//$totalMin = ($neededMin > 0) ? $neededMin * $count : $count;
				//$totalMax = ($neededMax > 0) ? $neededMax * $count : $count;

				$item = $this->getItem($id);

				$reagent = [
					'icon' => ($item != null) ? $item['icon'] : null,
					'item_id' => $id,
					'count' => $count,
					'item' => $this->buildItem($item),
					//'total_min' => $totalMin,
					//'total_max' => $totalMax,
				];

				// going deeper?
				$foundRecipe = null;
				
				if (!in_array($id, $trunk)) {
					$srcRecipes = $this->db->getRecipesByItemId($id);

					if (!empty($srcRecipes)) {
						foreach ($srcRecipes as $srcRecipe) {
							// skipping transmutes
							if (preg_match('/^Transmute/', $srcRecipe['name'])) {
								continue;
							}
							
							$srcRegs = $this->extractRecipeReagents($srcRecipe['reagents']);
							
							$badReagents = array_filter(array_keys($srcRegs), function($srcRegId) use ($trunk) {
								return in_array($srcRegId, $trunk);
							});
							
							if (empty($badReagents)) {
								$foundRecipe = $this->buildRecipe($srcRecipe, $cacheEnabled, $requiredSkills, $trunk);
								break;
							}
						}
					}
				}
				
				$reagent['recipe'] = $foundRecipe;

				$reagents[] = $reagent;
			}

			if ($cacheEnabled) {
				$this->db->setRecipeReagentCache($recipe['id'], json_encode($reagents));
			}
		}

		// link
		if ($cacheEnabled && strlen($recipe['icon_cache']) > 0) {
			$link = json_decode($recipe['icon_cache'], true);
		}
		else {
			if ($recipe['creates_id'] != 0) {
				$item = $this->getItem($recipe['creates_id']);
				
				$link = [
					'icon' => ($item != null) ? $item['icon'] : null,
					'item_id' => $recipe['creates_id'],
					'count' => $createsMin,
					'max_count' => $createsMax,
					'spell_id' => $recipe['id'],
				];
			}
			else {
				$icon = $this->getSpellIcon($recipe['id']);
				$link =	[
					'icon' => $icon,
					'spell_id' => $recipe['id'],
				];
			}

			if ($cacheEnabled) {
				$this->db->setRecipeIconCache($recipe['id'], json_encode($link));
			}
		}

		$recipe['inv_quality'] = $this->invertQuality($recipe['quality']);
		$recipe['sources'] = $sources;
		$recipe['url'] = $this->router->recipe($recipe['id']);
		$recipe['link'] = $this->buildRecipeLink($link);

		$recipe['reagents'] = array_map(function($r) {
			return $this->buildRecipeLink($r);
		}, $reagents);

		$recipe = $this->addNodeIds($recipe);
		$recipe = $this->addTotals($recipe);

		if ($topLevel) {
			$baseReagents = $this->getRecipeBaseReagents($recipe);
			
			$recipe['base_reagents'] = array_map(function($r) {
				return $this->buildRecipeLink($r);
			}, array_values($baseReagents));
			
			$recipe['required_skills'] = $requiredSkills;
		}

		return $recipe;
	}
	
	private function defaultIcon() {
		return $this->getSettings('legacy.recipes.default_icon');
	}
	
	private function buildRecipeLink($link) {
		$link['icon_url'] = $this->router->wowheadIcon($link['icon'] ?? $this->defaultIcon());

		if (isset($link['item_id'])) {
			$link['item_url'] = $this->router->wowheadItemRu($link['item_id']);
		}
		
		if (isset($link['spell_id'])) {
			$link['spell_url'] = $this->router->wowheadSpellRu($link['spell_id']);
		}
		
		$link['url'] = $link['item_url'] ?? $link['spell_url'];

		return $link;
	}
	
	private function buildItem($item) {
		$item['name_ru'] = $item['name_ru'] ?? $item['name'];
		
		$item['url'] = $this->router->wowheadItemRu($item['id']);

		return $item;
	}
	
	public function buildSkill($skill) {
		$skill['icon_url'] = $this->router->wowheadIcon($skill['icon'] ?? $this->defaultIcon());

		return $skill;
	}

	public function buildRecipesPaging($index, $skill = null, $query = null, $pageSize) {
		$paging = [];
		$pages = [];
		
		$stepping = 10;
		$neighbours = 2;
		
		$count = $this->db->getRecipeCount($skill['id'], $query);

		if ($count > $pageSize) {
			$url = $this->router->recipes($skill);

			if ($query) {
				$url .= '?q=' . htmlspecialchars($query);
			}

			// prev page
			if ($index > 1) {
        		$prev = $this->buildPage($url, $index - 1, false, $this->decorator->prev(), 'Предыдущая страница');
        		$pages[] = $prev;
        		$paging['prev'] = $prev;
			}

			$pageCount = ceil($count / $pageSize);
			
			$shownIndex = 1;
			$step = ceil($pageCount / $stepping);

			while ($shownIndex <= $pageCount) {
				if ($shownIndex == 1 ||
					$shownIndex >= $pageCount ||
					($shownIndex % $step == 0) ||
					(abs($shownIndex - $index) <= $neighbours)) {
					$pages[] = $this->buildPage($url, $shownIndex, $shownIndex == $index);
				}
				
				$shownIndex++;
			}

			// next page
			if ($index < $pageCount) {
				$next = $this->buildPage($url, $index + 1, false, $this->decorator->next(), 'Следующая страница');
				$pages[] = $next;
				$paging['next'] = $next;
			}
			
			$paging['pages'] = $pages;
		}
		
		return $paging;
	}
	
	protected function stamps($data, $shortDates = false) {
		if ($data['created_by']) {
			$row = $this->db->getUser($data['created_by']);
			$data['author'] = $this->buildUser($row);
		}
		
		if ($data['updated_by']) {
			$row = $this->db->getUser($data['updated_by']);
			$data['editor'] = $this->buildUser($row);
		}
		
		if ($shortDates) {
			$data['created_at'] = $this->formatDate(strtotime($data['created_at']));
			$data['updated_at'] = $this->formatDate(strtotime($data['updated_at']));
		}
		else {
			$data['created_at'] = $this->formatDateTime(strtotime($data['created_at']));
			$data['updated_at'] = $this->formatDateTime(strtotime($data['updated_at']));
		}
		
		return $data;
	}

	public function buildArticleLink($row) {
		$cat = is_array($row['cat'])
			? $row['cat']
			: $this->db->getCat($row['cat']);

		$ru = $row['name_ru'];
		$en = $row['name_en'];

		return [
			'cat' => $cat,
			'url' => $this->router->article($en, $cat['name_en']),
			'title' => $ru,
			'title_en' => $row['hideeng'] ? $ru : $en,
			'title_full' => $ru . (!$row['hideeng'] ? " ({$en})" : ''),
			'game' => $row['game'] ?? $this->db->getGame($row['game_id']),
		];
	}
	
	public function buildLatestArticles($filterByGame, $limit, $exceptArticleId) {
		$rows = $this->db->getLatestArticles($filterByGame, $limit, $exceptArticleId);

		return array_map(function($row) use ($filterByGame) {
			return $this->buildArticleLink($row);
		}, $rows);
	}
	
	public function buildMenu($game) {
		if (!$game) {
			throw new \Exception('Game cannot be null.');
		}
		
		$menus = $this->db->getMenus($game['id']);
		
		return array_map(function($menu) {
			$menu['items'] = $this->db->getMenuItems($menu['id']);
			return $menu;
		}, $menus ?? []);
	}
	
	public function buildSortedGalleryAuthors() {
		$rows = $this->db->getGalleryAuthors();
		
		$authors = [];
		
		foreach ($rows as $row) {
			$authors[] = $this->buildGalleryAuthor($row);
		}
			
		$sorts = [
			'count' => [ 'dir' => 'desc' ],
			'name' => [ 'dir' => 'asc', 'type' => 'string' ],
		];

		$sort = new Sort;
		$authors = $sort->multiSort($authors, $sorts);
		
		return $authors;
	}

	public function buildGalleryAuthor($row, $short = false) {
		$author = $row;

		$author['page_url'] = $this->router->galleryAuthor($author['alias']);

		if (!$short) {
			$picRows = $this->db->getGalleryPictures($author['id']);
			
			$author['count'] = count($picRows);
			
			if ($author['count'] > 0) {
				$last = $picRows[0];

				$author['last_picture_id'] = $last['id'];
				$author['last_thumb_url'] = $this->router->galleryThumbImg($last);
			}
	
			$author['pictures_str'] = $this->cases->caseForNumber('картинка', $author['count']);
			
			$forumMember = $this->db->getForumMemberByName($author['name']);
			
			if ($forumMember) {
				$author['member_id'] = $forumMember['member_id'];
				$author['member_url'] = $this->router->forumUser($author['member_id']);
			}
		}
		
		return $author;
	}
	
	public function buildGalleryPicture($row, $author = null) {
		$picture = $row;

		$id = $picture['id'];
		
		$picture['ext'] = $this->router->getExtension($picture['picture_type']);
		$picture['url'] = $this->router->galleryPictureImg($picture);
		$picture['thumb'] = $this->router->galleryThumbImg($picture);

		if ($author == null) {
			$authorRow = $this->db->getGalleryAuthor($picture['author_id']);
			$author = $this->builder->buildGalleryAuthor($authorRow, true);
		}

		if ($author != null) {
			$picture['author'] = $author;
			$picture['page_url'] = $this->router->galleryPicture($author['alias'], $id);
		}
		
		$prev = $this->db->getGalleryPicturePrev($picture);
		$next = $this->db->getGalleryPictureNext($picture);

		if ($prev != null) {
			$prev['page_url'] = $this->router->galleryPicture($author['alias'], $prev['id']);
			$picture['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->router->galleryPicture($author['alias'], $next['id']);
			$picture['next'] = $next;
		}

		return $this->stamps($picture, true);
	}
	
	public function buildPage($baseUrl, $page, $current, $label = null, $title = null) {
		return [
			'page' => $page,
			'current' => $current,
			'url' => $this->router->page($baseUrl, $page),
			'label' => ($label != null) ? $label : $page,
			'title' => ($title != null) ? $title : "Страница {$page}",
		];
	}

	public function buildPaging($baseUrl, $totalPages, $page) {
		if ($totalPages > 1) {
			$paging = [];
			$pages = [];
			
			if ($page > 1) {
				$prev = $this->buildPage($baseUrl, $page - 1, false, $this->decorator->prev(), 'Предыдущая страница');
				$paging['prev'] = $prev;
				$pages[] = $prev;
			}

			for ($i = 1; $i <= $totalPages; $i++) {
				$pages[] = $this->buildPage($baseUrl , $i, $i == $page);
			}
			
			if ($page < $totalPages) {
				$next = $this->buildPage($baseUrl, $page + 1, false, $this->decorator->next(), 'Следующая страница');
				$paging['next'] = $next;
				$pages[] = $next;
			}
			
			$paging['pages'] = $pages;

			return $paging;
		}
	}
	
	public function buildUser($row) {
		$user = $row;
		
		$forumMember = $this->db->getForumMemberByUser($user);
		
		if ($forumMember) {
			$user['member_url'] = $this->router->forumUser($forumMember['member_id']);
		}
		
		$user['name'] = $user['name'] ?? $user['login'];

		return $user;
	}
	
	public function buildStream($row) {
		$stream = $row;

		$streamTimeToLive = $this->getSettings('legacy.streams.ttl');
		$now = new \DateTime;

		$stream['priority_game'] = true;

		if ($stream['remote_online_at']) {
			$dt = new \DateTime($stream['remote_online_at']);
			$interval = $dt->diff($now);

			$stream['alive'] = ($interval->d < $streamTimeToLive);

			if ($stream['alive']) {
				$game = strtolower($stream['remote_game']) ?? '';
				$priorityGames = $this->getSettings('legacy.streams.priority_games');
				$stream['priority_game'] = in_array($game, $priorityGames);
			}
		}

		$id = $stream['stream_id'];
		
		$stream['stream_alias'] = $stream['stream_alias'] ?? $id;
		$stream['page_url'] = $this->router->stream($stream['stream_alias']);

		// only Twitch for now
		switch ($stream['type']) {
			// Twitch
			case 1:
				//$stream['img_url'] = "http://static-cdn.jtvnw.net/previews/live_user_{$id}-320x180.jpg";
				$stream['img_url'] = "https://static-cdn.jtvnw.net/previews-ttv/live_user_{$id}-320x180.jpg";
				$stream['large_img_url'] = "https://static-cdn.jtvnw.net/previews-ttv/live_user_{$id}-640x360.jpg";
				
				$stream['twitch'] = true;
				$stream['stream_url'] = "http://twitch.tv/{$id}";
				break;

			default:
				throw new \Exception('Unsupported stream type: ' . $stream['type']);
		}
		
		$onlineAt = $stream['remote_online_at'];
		
		if ($onlineAt) {
			$stream['remote_online_at'] = $this->formatDate(strtotime($onlineAt));
		}

		$stream['remote_online_ago'] = $this->dateToAgo($onlineAt);
		
		$form = [
			'time' => Cases::PAST,
			'person' => Cases::FIRST,
			'number' => Cases::SINGLE,
			'gender' => $stream['gender_id'],
		];
		
		$stream['played'] = $this->cases->conjugation('играть', $form);
		$stream['broadcasted'] = $this->cases->conjugation('транслировать', $form);
		$stream['held'] = $this->cases->conjugation('вести', $form);

		return $stream;
	}
	
	public function updateStreamData($row, $notify = false) {
		$stream = $row;
		
		$id = $stream['stream_id'];
		
		switch ($stream['type']) {
			// Twitch
			case 1:
				$data = $this->getTwitchStreamData($id);
				$json = json_decode($data, true);

				if (isset($json['streams'][0])) {
					$s = $json['streams'][0];

					$streamStarted = ($stream['remote_online'] == 0);

					$stream['remote_online'] = 1;
					$stream['remote_game'] = $s['game'];
					$stream['remote_viewers'] = $s['viewers'];
					
					if (isset($s['channel'])) {
						$ch = $s['channel'];

						$stream['remote_title'] = $ch['display_name'];
						$stream['remote_status'] = $ch['status'];
						$stream['remote_logo'] = $ch['logo'];
					}
					
					if ($notify && $streamStarted) {
						$message = $this->sendStreamNotifications($stream);
					}
				}
				else {
					$stream['remote_online'] = 0;
					$stream['remote_viewers'] = 0;
				}
				
				break;
			
			default:
				throw new \Exception('Unsupported stream type: ' . $stream['type']);
		}

		// save
		$this->db->saveStream($stream);
		
		// stats
		$this->updateStreamStats($stream);

		if ($s) {
			$stream['json'] = $data;
			$stream['message'] = $message;
		}

		return $stream;
	}
	
	private function updateStreamStats($stream) {
		$online = ($stream['remote_online'] == 1);
		
		$refresh = $online;
		
		$stats = $this->db->getLastStreamStats($stream['id']);
		
		if ($stats) {
			if ($online) {
				$statsTTL = $this->getSettings('legacy.streams.stats_ttl');
				$now = new \DateTime;
				$dt = new \DateTime($stats['created_at']);
				$interval = $dt->diff($now);
	
				if (($interval->i < $statsTTL) && ($stream['remote_game'] == $stats['remote_game'])) {
					$refresh = false;
				}
			}

			if (!$stats['finished_at'] && (!$online || $refresh)) {
				$this->db->finishStreamStats($stats['id']);
			}
		}
		
		if ($refresh) {
			$this->db->saveStreamStats($stream);
		}
	}
	
	private function sendStreamNotifications($s) {
		$verb = ($s['channel'] == 1)
			? ($s['remote_status']
				? "транслирует <b>{$s['remote_status']}</b>"
				: 'ведет трансляцию')
			: "играет в <b>{$s['remote_game']}</b>
{$s['remote_status']}";

		$verbEn = ($s['channel'] == 1)
			? ($s['remote_status']
				? "is streaming <b>{$s['remote_status']}</b>"
				: 'started streaming')
			: "is playing <b>{$s['remote_game']}</b>
{$s['remote_status']}";
		
		$source = "<a href=\"http://twitch.tv/{$s['stream_id']}\">{$s['title']}</a>";
		
		$message = $source . ' ' . $verb;
		$messageEn = $source . ' ' . $verbEn;

		$settings = [
			[
				'channel' => 'warcry',
				'condition' => $s['priority'] == 1 || $s['official'] == 1 || $s['official_ru'] == 1,
				'message' => $message,
			],
			[
				'channel' => 'warcry_streams',
				'condition' => true,
				'message' => $message,
			],
			[
				'channel' => 'blizzard_streams',
				'condition' => $s['official'] == 1,
				'message' => $messageEn,
			],
			[
				'channel' => 'blizzard_streams_ru',
				'condition' => $s['official_ru'] == 1,
				'message' => $message,
			],
		];

		foreach ($settings as $setting) {
			if ($setting['condition']) {
				$this->notifyTelegram($setting['message'], $setting['channel']);
			}
		}

		return $message . ' ' . $messageEn;
	}
	
	private function notifyTelegram($message, $channelId) {
		$tgs = $this->getSettings('telegram');
		
		$botToken = $tgs['bot_token'];
		$channel = $tgs['channels'][$channelId];

		$this->curlTelegramSendMessage($botToken, $channel, $message);
	}

	private function curlTelegramSendMessage($botToken, $chatId, $message) {
		$url = "https://api.telegram.org/bot{$botToken}/sendMessage";
		$params = [
		    'chat_id' => '@' . $chatId,
		    'text' => $message,
		    'parse_mode' => 'html',
		];
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}

	private function getTwitchStreamData($id) {
		$url = "https://api.twitch.tv/kraken/streams?channel={$id}";
		$clientId = $this->getSettings('twitch.client_id');
		$data = $this->curlGetFromTwitch($url, $clientId);

		return $data;
	}

	private function curlGetFromTwitch($url, $clientId) {
		$ch = curl_init();

		$headers = [ "Client-ID: {$clientId}" ];
	
		curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}

	public function buildSortedStreams() {
		$streams = array_map(function($s) {
			return $this->buildStream($s);
		}, $this->db->getStreams());

		$sorts = [
			'remote_online' => [ 'dir' => 'desc' ],
			'official_ru' => [ 'dir' => 'desc' ],
			'official' => [ 'dir' => 'desc' ],
			'priority' => [ 'dir' => 'desc' ],
			'priority_game' => [ 'dir' => 'desc' ],
			'remote_viewers' => [ 'dir' => 'desc' ],
			'title' => [ 'dir' => 'asc', 'type' => 'string' ],
		];
		
		$sort = new Sort;
		$streams = $sort->multiSort($streams, $sorts);
		
		return $streams;
	}
	
	public function buildStreamGroups($streams) {
		$groupSettings = [
			[
				'id' => 'online',
				'label' => 'Онлайн',
				'telegram' => 'warcry_streams',
				'condition' => function($s) {
					return $s['remote_online'];
				},
			],
			[
				'id' => 'offline',
				'label' => 'Офлайн',
				'telegram' => 'warcry_streams',
				'condition' => function($s) {
					return $s['alive'] && !$s['remote_online'];
				},
			],
			[
				'id' => 'blizzard',
				'label' => 'Blizzard EN',
				'telegram' => 'blizzard_streams',
				'telegram_label' => 'официальных трансляций (англ.)',
				'condition' => function($s) {
					return $s['official'];
				},
			],
			[
				'id' => 'blizzard_ru',
				'label' => 'Blizzard РУ',
				'telegram' => 'blizzard_streams_ru',
				'telegram_label' => 'официальных трансляций (рус.)',
				'condition' => function($s) {
					return $s['official_ru'];
				},
			],
		];
		
		$groups = [];
		
		foreach ($groupSettings as $gs) {
			$gs['streams'] = array_filter($streams, $gs['condition']);
			$groups[] = $gs;
		}
		
		return $groups;
	}
	
	public function buildOnlineStream($filterByGame) {
		$streams = $this->buildSortedStreams();
	
		$onlineStreams = array_filter($streams, function($stream) {
			return $stream['remote_online'] == 1;
		});
		
		$totalOnline = count($onlineStreams);
	
		if ($totalOnline > 0) {
			$onlineStream = $onlineStreams[0];
			$onlineStream['total_streams_online'] = $totalOnline . ' ' . $this->cases->caseForNumber('стрим', $totalOnline);
		}
		
		return $onlineStream;
	}
	
	// COMICS

	public function buildSortedComicSeries() {
		$rows = $this->db->getComicSeries();

		$series = [];
		
		foreach ($rows as $row) {
			$series[] = $this->buildComicSeries($row);
		}
			
		$sorts = [
			'last_issued_on' => [ 'dir' => 'desc', 'type' => 'string' ],
		];

		$sort = new Sort;
		$series = $sort->multiSort($series, $sorts);
		
		return $series;
	}

	public function buildComicSeries($row) {
		$series = $row;
		
		$series['game'] = $this->db->getGame($series['game_id']);

		$series['page_url'] = $this->router->comicSeries($series['alias']);

		$comicRows = $this->db->getComicIssues($series['id']);
		$comicCount = count($comicRows);
		
		if ($comicCount > 0) {
			$series['cover_url'] = $this->getComicIssueCover($comicRows[0]['id']);
			$series['last_issued_on'] = $comicRows[$comicCount - 1]['issued_on'];
		}
		
		$series['comic_count'] = $comicCount;
		$series['comic_count_str'] = $this->cases->caseForNumber('выпуск', $comicCount);

		$series['publisher'] = $this->db->getComicPublisher($series['publisher_id']);
		
		if ($series['name_ru'] == $series['name_en']) {
			$series['name_en'] = null;
		}

		return $series;
	}

	public function buildSortedComicStandalones() {
		$rows = $this->db->getComicStandalones();

		$comics = [];
		
		foreach ($rows as $row) {
			$comics[] = $this->buildComicStandalone($row);
		}

		return $comics;
	}

	public function buildComicStandalone($row) {
		$comic = $row;
		
		$comic['game'] = $this->db->getGame($comic['game_id']);

		$comic['page_url'] = $this->router->comicStandalone($comic['alias']);

		$pageRows = $this->db->getComicStandalonePages($comic['id']);
		
		if (count($pageRows) > 0) {
			$pageRow = $pageRows[0];
			$comic['cover_url'] = $this->router->comicThumbImg($pageRow);
		}

		$comic['publisher'] = $this->db->getComicPublisher($comic['publisher_id']);
		$comic['issued_on'] = $this->formatDate(strtotime($comic['issued_on']));

		if ($comic['name_ru'] == $comic['name_en']) {
			$comic['name_en'] = null;
		}

		return $comic;
	}
	
	private function padNum($num) {
		return str_pad($num, 2, '0', STR_PAD_LEFT);
	}
	
	private function comicNum($comic) {
		$numStr = '#' . $comic['number'];
		
		if ($comic['name_ru']) {
			$numStr .= ': ' . $comic['name_ru'];
		}

		return $numStr;
	}
	
	private function pageNum($num) {
		return $this->padNum($num);
	}
	
	private function getComicIssueCover($comicId) {
		$pageRows = $this->db->getComicIssuePages($comicId);
		
		if (count($pageRows) > 0) {
			$cover = $this->router->comicThumbImg($pageRows[0]);
		}
		
		return $cover;
	}

	public function buildComicIssue($row, $series) {
		$comic = $row;

		$comic['page_url'] = $this->router->comicIssue($series['alias'], $comic['number']);
		$comic['cover_url'] = $this->getComicIssueCover($comic['id']);
		$comic['number_str'] = $this->comicNum($comic);
		$comic['issued_on'] = $this->formatDate(strtotime($comic['issued_on']));

		$prev = $this->db->getComicIssuePrev($comic);
		$next = $this->db->getComicIssueNext($comic);
		
		if ($prev != null) {
			$prev['page_url'] = $this->router->comicIssue($series['alias'], $prev['number']);
			$prev['number_str'] = $this->comicNum($prev);
			$comic['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->router->comicIssue($series['alias'], $next['number']);
			$next['number_str'] = $this->comicNum($next);
			$comic['next'] = $next;
		}

		return $comic;
	}
	
	public function buildComicIssuePage($row, $series, $comic) {
		$page = $row;

		$id = $page['id'];
		
		$page['url'] = $this->router->comicPageImg($page);
		$page['thumb'] = $this->router->comicThumbImg($page);
		$page['page_url'] = $this->router->comicIssuePage($series['alias'], $comic['number'], $page['number']);
		$page['number_str'] = $this->pageNum($page['number']);

		$prev = $this->db->getComicIssuePagePrev($comic, $page);
		$next = $this->db->getComicIssuePageNext($comic, $page);
		
		if ($prev != null) {
			$prev['page_url'] = $this->router->comicIssuePage($series['alias'], $prev['comic']['number'], $prev['number']);
			$prev['comic_number_str'] = $this->comicNum($prev['comic']);
			$prev['number_str'] = $this->pageNum($prev['number']);
			$page['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->router->comicIssuePage($series['alias'], $next['comic']['number'], $next['number']);
			$next['comic_number_str'] = $this->comicNum($next['comic']);
			$next['number_str'] = $this->pageNum($next['number']);
			$page['next'] = $next;
		}
		
		$page['ext'] = 'jpg';

		return $page;
	}
	
	public function buildComicStandalonePage($row, $comic) {
		$page = $row;

		$id = $page['id'];
		
		$page['url'] = $this->router->comicPageImg($page);
		$page['thumb'] = $this->router->comicThumbImg($page);
		$page['page_url'] = $this->router->comicStandalonePage($comic['alias'], $page['number']);
		$page['number_str'] = $this->pageNum($page['number']);

		$prev = $this->db->getComicStandalonePagePrev($page);
		$next = $this->db->getComicStandalonePageNext($page);
		
		if ($prev != null) {
			$prev['page_url'] = $this->router->comicStandalonePage($comic['alias'], $prev['number']);
			$prev['number_str'] = $this->pageNum($prev['number']);
			$page['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->router->comicStandalonePage($comic['alias'], $next['number']);
			$next['number_str'] = $this->pageNum($next['number']);
			$page['next'] = $next;
		}
		
		$page['ext'] = 'jpg';

		return $page;
	}
	
	private function dateToAgo($date) {
		if ($date) {
			$now = new \DateTime;
			$today = new \DateTime("today");
			$yesterday = new \DateTime("yesterday");		

			$dt = new \DateTime($date);
	
			if ($dt > $today) {
				$str = 'сегодня';
			}
			elseif ($dt > $yesterday) {
				$str = 'вчера';
			}
			else {
				$interval = $dt->diff($now);
				$days = $interval->d;
				$str = $days . ' ' . $this->cases->caseForNumber('день', $days) . ' назад';
			}
		}
		
		return $str ?? 'неизвестно когда';
	}
	
	private function getSubArticles($parentId) {
		$rows = $this->db->getSubArticles($parentId);
		if ($rows) {
			foreach ($rows as $row) {
				$item = $this->buildArticleLink($row);
				$item['items'] = $this->getSubArticles($row['id']);
				$items[] = $item;
			}
		}

		return $items;
	}

	public function buildMap() {
		$rootId = $this->getSettings('legacy.articles.root_id');
		
		return $this->getSubArticles($rootId);
	}
}
