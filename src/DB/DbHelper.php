<?php

namespace App\DB;

use Warcry\Util\Util;
use Warcry\ORM\Idiorm\DbHelper as DbHelperBase;

use App\DB\Tables;

class DbHelper extends DbHelperBase {
	private function getTableHelper($table) {
		return new TableHelper($this->container, $table);
	}
	
	protected function can($table, $rights, $item = null) {
		$tableHelper = $this->getTableHelper($table);
		
		$access = $item
			? $tableHelper->getRights($item)
			: $tableHelper->getTableRights();

		return $access[$rights];
	}

	private function addUserNames($item) {
		if (isset($item['created_by'])) {
			$created = $this->getUser($item['created_by']);
			if ($created !== null) {
				$item['created_by_name'] = $created['login'];
			}
		}

		if (isset($item['updated_by'])) {
			$updated = $this->getUser($item['updated_by']);
			if ($updated !== null) {
				$item['updated_by_name'] = $updated['login'];
			}
		}
		
		return $item;
	}

	public function apiGetMany($table, $provider, $options = []) {
		$exclude = $options['exclude'] ?? null;

		$items = $this->selectMany($table, $exclude);
		
		if (isset($options['filter'])) {
			$items = $this->filterBy($items, $options['filter'], $options['args']);
		}

		$settings = $this->tables[$table];
		
		if (isset($settings['sort'])) {
			$sortBy = $settings['sort'];
			$reverse = isset($settings['reverse']);
			$items = $reverse
				? $items->orderByDesc($sortBy)
				: $items->orderByAsc($sortBy);
		}
		
		$array = $items->findArray();
		
		$tableHelper = $this->getTableHelper($table);

		$array = array_filter($array, array($tableHelper, 'canRead'));
		$array = array_map(array($provider, 'afterLoad'), $array);
		$array = array_map(array($this, 'addUserNames'), $array);
		$array = array_map(array($tableHelper, 'addRights'), $array);

		return array_values($array);
	}

	protected function beforeValidate($request, $table, $data, $id = null) {
		// unset
		$canPublish = $this->can($table, 'publish');
		
		if (isset($data['published']) && !$canPublish) {
			unset($data['published']);
		}

		if (isset($data['password'])) {
			$password = $data['password'];
			if (strlen($password) > 0) {
				$data['password'] = Util::encodePassword($password);
			}
			else {
				unset($data['password']);
			}
		}

		// dirty
		/*if ($this->hasField($table, 'created_at') && !$id) {
			$data['created_at'] = Util::now();
		}*/

		$upd = $this->updatedAt($table);
		if ($upd) {
			$data['updated_at'] = $upd;
		}
		
		$user = $this->auth->getUser();
		if ($this->hasField($table, 'created_by') && !$id) {
			$data['created_by'] = $user->id;
		}
		
		if ($this->hasField($table, 'updated_by')) {
			$data['updated_by'] = $user->id;
		}

		return $data;
	}
	
	private function updatedAt($table) {
		return $this->hasField($table, 'updated_at')
			? Util::now()
			: null;
	}

	// SHORTCUTS
	private function asArray($obj) {
		return $obj ? $obj->asArray() : null;
	}

	protected function get($table, $id) {
		$obj = $this->getObj($table, $id);
		return $this->asArray($obj);
	}
	
	protected function getObj($table, $id) {
		return $this
			->forTable($table)
			->where('id', $id)
			->findOne();
	}

	protected function getBy($table, $where) {
		$obj = $this->getObjBy($table, $where);
		return $this->asArray($obj);
	}
	
	protected function getObjBy($table, $where) {
		$query = $this->forTable($table);
		return $where($query)->findOne();
	}
	
	private function getManyBaseQuery($table, $where = null) {
		$query = $this->forTable($table);

		if ($where) {
			$query = $where($query);
		}
		
		return $query;
	}
	
	protected function getArray($query) {
		$result = $query->findArray();
		return $result ? array_values($result) : null;
	}
	
	protected function getMany($table, $where = null) {
		$query = $this
			->getManyBaseQuery($table, $where);
		
		return $this->getArray($query);
	}
	
	protected function getManyObj($table, $where = null) {
		return $this
			->getManyBaseQuery($table, $where)
			->findMany();
	}
	
	protected function getManyByField($table, $field, $value) {
		return $this->getMany($table, function($q) use ($field, $value) {
			return $q->where($field, $value);
		});
	}

	protected function getObjByField($table, $field, $value, $where = null) {
		$query = $this
			->forTable($table)
			->where($field, $value);
			
		if ($where) {
			$query = $where($query);
		}

		return $query->findOne();
	}

	protected function getByField($table, $field, $value, $where = null) {
		$obj = $this->getObjByField($table, $field, $value, $where);
		return $this->asArray($obj);
	}

	protected function getIdByField($table, $field, $value, $where = null) {	
		$obj = $this->getObjByField($table, $field, $value, $where);
		return $obj ? $obj->id : null;
	}
	
	protected function getIdByName($table, $name, $where = null) {
		return $this->getIdByField($table, 'name', $name, $where);
	}

	protected function setField($table, $id, $field, $value) {
		return $this->set($table, $id, [ $field => $value ]);
	}
	
	protected function set($table, $id, $data) {
		$obj = $this->getObj($table, $id);
		
		if (!$obj) {
			$obj = $this->forTable($table)->create();
			$obj->id = $id;
		}
		else {
			$upd = $this->updatedAt($table);
			if ($upd) {
				$obj->updated_at = $upd;
			}
		}

		$obj->set($data);
		$obj->save();
		
		return $this->asArray($obj);
	}
	
	// ARTICLES
	
	public function getCat($id) {
		return $this->get(Tables::ARTICLE_CATEGORIES, $id);
	} 
	
	// returns cat by cat name_en
	public function getCatIdByName($name) {
		return $this->getIdByField(Tables::ARTICLE_CATEGORIES, 'name_en', $name);
	}

	// returns article by id (id - numeric, name_en - text) and cat id (optional)
	public function getArticle($id, $cat = null) {
		$query = $this
			->forTable(Tables::ARTICLES)
			->where('published', 1)
			->whereGte('parent_id', 0);

		if (is_numeric($id)) {
			$query = $query
				->where('id', $id);
		}
		else {
			$query = $query
				->where('name_en', $id);

			if ($cat) {
				$catId = $this->getCatIdByName($cat);
			}

			if ($catId) {
				$query = $query
					->whereRaw('(cat = ? or cat is null)', [ $catId ])
					->orderByDesc('cat');
			}
			else {
				$query = $query
					->orderByAsc('cat');
			}
		}

		return $this->asArray($query->findOne());
	}

	// returns sub articles by article id (numeric strict)
	public function getSubArticles($parentId) {
		return $this->getMany(Tables::ARTICLES, function($q) use ($parentId) {
			return $q
				->where('parent_id', $parentId)
				->where('published', 1)
				->orderByAsc('name_ru');
		});
	}

	public function getItemIdByName($name) {
		return $this->getIdByName(Tables::ITEMS, $name);
	}

	public function getNPCIdByName($name) {
		return $this->getIdByName(Tables::NPC, $name);
	}

	public function getSpellIdByName($name, $skill = null) {
		return $this->getIdByName(Tables::SPELLS, $name, function($q) use ($skill) {
			return $skill
				? $q->where('skill', $skill)
				: $q;
		});
	}

	public function getQuestIdByName($name) {
		return $this->getIdByName(Tables::QUESTS, $name);
	}

	public function getLocationIdByName($name) {
		return $this->getIdByName(Tables::LOCATIONS, $name);
	}

	public function saveArticleCache($id, $cache) {
		$this->setField(Tables::ARTICLES, $id, 'cache', $cache);
	}

	public function saveArticleContentsCache($id, $contentsCache) {
		$this->setField(Tables::ARTICLES, $id, 'contents_cache', $contentsCache);
	}

	// RECIPES
	
	private function getRecipeQuery($skill = null, $q = null) {
		$query = $this
			->forTable(Tables::RECIPES);
			//->whereNotEqual('quality', '@');

		if ($skill) {
			$query = $query->where('skill', $skill);
		}

		if ($q) {
			$qParts = preg_split("/\s/", $q);
			foreach ($qParts as $qPart) {
				$decor = '%' . $qPart . '%';
				$query = $query
					->whereRaw('(name like ? or name_ru like ?)', [ $decor, $decor ]);
			}
		}

		return $query;
	}

	public function getRecipes($offset = 0, $limit = 0, $skill = null, $q = null) {
		$query = $this->getRecipeQuery($skill, $q)
			->orderByAsc('learnedat')
			->orderByAsc('lvl_orange')
			->orderByAsc('lvl_yellow')
			->orderByAsc('lvl_green')
			->orderByAsc('lvl_gray')
			->orderByAsc('name_ru');
		
		if ($limit > 0) {
			$query = $query
				->offset($offset)
				->limit($limit);
		}

		return $this->getArray($query);
	}

	function getRecipeCount($skill = null, $q = null) {
		$query = $this->getRecipeQuery($skill, $q);
		return $query->count();
	}

	public function getRecipeSources() {
		return $this->getMany(Tables::RECIPE_SOURCES);
	}

	public function getRecipeSource($id) {
		return $this->get(Tables::RECIPE_SOURCES, $id);
	}

	public function getSpellIcon($id) {
		return $this->get(Tables::SPELL_ICONS, $id);
	}

	public function setRecipeReagentIcons($id, $reagentIcons) {
		$this->setField(Tables::RECIPES, $id, 'reagent_icons', $reagentIcons);
	}

	public function getSkills() {
		return $this->getMany(Tables::SKILLS, function($q) {
			return $q->where('active', 1);
		});
	}

	public function getSkillByAlias($alias) {
		return $this->getByField(Tables::SKILLS, 'alias', $alias);
	}

	public function getSkill($id) {
		return $this->get(Tables::SKILLS, $id);
	}

	public function setRecipeIcon($id, $icon) {
		$this->setField(Tables::RECIPES, $id, 'icon', $icon);
	}

	public function setRecipeReagentCache($id, $reagentCache) {
		$this->setField(Tables::RECIPES, $id, 'reagent_cache', $reagentCache);
	}

	public function setRecipeIconCache($id, $iconCache) {
		$this->setField(Tables::RECIPES, $id, 'icon_cache', $iconCache);
	}

	public function getRecipe($id) {
		return $this->get(Tables::RECIPES, $id);
	}

	public function getRecipeByName($name) {
		return $this->getBy(Tables::RECIPES, function($q) use ($name) {
			return $q
				->whereRaw('(name like ? or name_ru like ?)', [ $name, $name ]);
		});
	}

	public function getRecipesByItemId($itemId) {
		return $this->getMany(Tables::RECIPES, function($q) use ($itemId) {
			return $q
				->where('creates_id', $itemId)
				->whereGt('creates_min', 0);
		});
	}

	/*public function getRecipeByItemId($itemId) {
		$sources = $this->getRecipesByItemId($itemId);
		
		if (is_array($sources)) {
			foreach ($sources as $source) {
				$srcId = $source['id'];
				$srcName = $source['name'];
				$srcNameRu = $source['name_ru'];
				
				// исключаем "трансмуты" кожи и  прочие циклы
				$forbidden = [ 50936, 64661, 32455, 32454, 22331, 20650, 20649, 20648, 2881, 12716, 13240 ];
				
				// разрешаем трансмуты, которые не зацикливаются и без которых не произвести тех или иных регов
				$allowed = [ 11480, 17187, 32765, 29688, 32766, 57427, 57425, 66658, 66662, 66664, 66660, 66663, 66659 ];
				
				if (in_array($srcId, $allowed) || (!in_array($srcId, $forbidden) && !preg_match('/^Transmute/', $srcName))) {
					$result = $source;
					break;
				}
			}
		}

		return $result;
	}*/

	public function getItem($id) {
		return $this->get(Tables::ITEMS, $id);
	}

	public function saveItem($id, $item) {
		$this->set(Tables::ITEMS, $id, $item);
	}

	public function getItems() {
		return $this->getMany(Tables::ITEMS);
	}

	public function getReplaces() {
		return $this->getMany(Tables::REPLACES);
	}

	// NEW STUFF

	private function getNewsForumIds($filterByGame) {
		$games = $filterByGame
			? [ $filterByGame ]
			: $this->getGames();
		
		return array_column($games, 'news_forum_id');
	}

	public function getLatestForumNews($filterByGame, $offset, $limit, $exceptNewsId = null) {
		$forumIds = $this->getNewsForumIds($filterByGame);

		$query = $this
			->forTable(Tables::FORUM_TOPICS)
			->whereIn('forum_id', $forumIds);

		if ($exceptNewsId) {
			$query = $query->whereNotEqual('tid', $exceptNewsId);
		}

		$query = $query
			->orderByDesc('start_date')
			->offset($offset)
			->limit($limit);

		$topics = $this->getArray($query);

		return array_map(function($topic) {
			$post = $this->getForumTopicPost($topic['tid']);
			if ($post) {
				$topic['post'] = $post['post'];
			}

			return $topic;
		}, $topics);
	}
	
	public function getForumTopicPost($topicId) {
		return $this->getBy(Tables::FORUM_POSTS, function($q) use ($topicId) {
			return $q
				->where('topic_id', $topicId)
				->where('new_topic', 1);
		});
	}

	public function getForumNews($id) {
		$news = $this->getByField(Tables::FORUM_TOPICS, 'tid', $id);
		
		$post = $this->getForumTopicPost($id);
		if ($post) {
			$news['post'] = $post['post'];
		}
		
		return $news;
	}

	public function getLatestNews($filterByGame, $offset, $limit, $exceptNewsId = null) {
		$query = $this
			->forTable(Tables::NEWS)
			->where('published', 1)
   			->whereRaw('(published_at < now())', []);

		if ($exceptNewsId) {
			$query = $query->whereNotEqual('id', $exceptNewsId);
		}
		
		if ($filterByGame) {
			$query = $query->where('game_id', $filterByGame['id']);
		}

		$query = $query
			->orderByDesc('published_at')
			->offset($offset)
			->limit($limit);

		return $this->getArray($query);
	}
	
	public function getNews($id) {
		$item = $this->get(Tables::NEWS, $id);
		$can = $this->can(Tables::NEWS, 'edit_own', $item);
		
		return $this->getBy(Tables::NEWS, function($q) use ($id, $can) {
			$q = $q->where('id', $id);
			
			if (!$can) {
				$q = $q
					->where('published', 1)
					->whereRaw('(published_at < now())');
			}
			
			return $q;
		});
	}

	public function saveNewsCache($id, $cache) {
		$this->setField(Tables::NEWS, $id, 'cache', $cache);
	}

	public function getForumTopicTags($topicId) {
		return $this->getMany(Tables::FORUM_TAGS, function($q) use ($topicId) {
			return $q
				->where('tag_meta_app', 'forums')
				->where('tag_meta_area', 'topics')
				->where('tag_meta_id', $topicId);
		});
	}

	public function getLatestArticles($filterByGame, $limit, $exceptArticleId) {
		$query = $this
			->forTable(Tables::ARTICLES)
			->where('published', 1)
			->where('announce', 1);
			
		if ($filterByGame) {
			$query = $query->where('game_id', $filterByGame['id']);
		}
		
		if ($exceptArticleId) {
			$query = $query->whereNotEqual('id', $exceptArticleId);
		}

		$query = $query
			->orderByDesc('created_at')
			->limit($limit);
		
		return $this->getArray($query);
	}
	
	public function getLatestForumTopics($filterByGame, $limit) {
		$query = $this
			->forTable(Tables::FORUM_TOPICS)
			->whereNotEqual('state', 'link')
			->whereGt('posts', 0);

		$hiddenIds = $this->getSettings('legacy.hidden_forum_ids');
		$query = $query->whereNotIn('forum_id', $hiddenIds);

		if ($filterByGame != null) {
			$forums = $this->getForumsByGameId($filterByGame['id']);
			if (count($forums) > 0) {
				$forumIds = array_column($forums, 'id');
				$query = $query->whereIn('forum_id', $forumIds);
			}
		}

		$result = $query
			->orderByDesc('last_post')
			->limit($limit)
			->findArray();
		
		return $result;
	}
	
	public function getDefaultGameId() {
		return $this->getSettings('legacy.default_game_id');
	}

	public function getGames() {
		return $this->getMany(Tables::GAMES, function($q) {
			return $q
				->where('published', 1)
				->orderByAsc('position');	
		});
	}

	public function getGame($id) {
		return $this->get(Tables::GAMES, $id);
	}
	
	public function getGameByAlias($alias) {
		return $this->getByField(Tables::GAMES, 'alias', $alias);
	}

	public function getDefaultGame() {
		$id = $this->getDefaultGameId();
		return $this->getGame($id);
	}

	public function getForums() {
		return $this->getMany(Tables::FORUMS);
	}

	public function getForum($id) {
		return $this->get(Tables::FORUMS, $id);
	}

	public function getGameByForumId($forumId) {
		$path = 'gamesByForumId.' . $forumId;
		$game = $this->cache->get($path);
		
		if (!$games) {
			$games = $this->getGames();
			$foundGame = null;

			$curForumId = $forumId;
			
			while (!$foundGame && $curForumId != -1) {
				foreach ($games as $game) {
					if ($game['news_forum_id'] == $curForumId || $game['main_forum_id'] == $curForumId) {
						$foundGame = $game;
						break;
					}
				}

				if (!$foundGame) {
					$forum = $this->getForum($curForumId);
					$curForumId = $forum['parent_id'];
				}
			}

			$this->cache->set($path, $foundGame ?? $this->getDefaultGame());
		}

		return $this->cache->get($path);
	}
	
	public function getForumsByGameId($gameId) {
		$result = [];

		$forums = $this->getForums();

		foreach ($forums as $forum) {
			$game = $this->getGameByForumId($forum['id']);
			if ($game['id'] == $gameId) {
				$result[] = $forum;
			}
		}
		
		return $result;
	}

	public function getMenus($gameId) {
		return $this->getMany(Tables::MENUS, function($q) use ($gameId) {
			return $q
				->where('game_id', $gameId)
				->orderByAsc('position');
		});
	}

	public function getMenuItems($menuId) {
		return $this->getMany(Tables::MENU_ITEMS, function($q) use ($menuId) {
			return $q
				->where('section_id', $menuId)
				->orderByAsc('position');
		});
	}

	public function getGalleryAuthors() {
		return $this->getMany(Tables::GALLERY_AUTHORS, function($q) {
			return $q
				->where('published', 1);
		});
	}
	
	public function getGalleryAuthor($id) {
		return $this->getBy(Tables::GALLERY_AUTHORS, function($q) use ($id) {
			return $q
				->where('id', $id)
				->where('published', 1);
		});
	}

	public function getGalleryAuthorByAlias($alias) {
		return $this->getBy(Tables::GALLERY_AUTHORS, function($q) use ($alias) {
			return $q
				->where('alias', $alias)
				->where('published', 1);
		});
	}
	
	public function getGalleryPictures($authorId, $offset = 0, $limit = 0) {
		return $this->getMany(Tables::GALLERY_PICTURES, function($q) use ($authorId, $offset, $limit) {
			$q = $q
				->where('author_id', $authorId)
				->where('published', 1)
				->orderByDesc('created_at');
			
			if ($limit > 0) {
				$q = $q
					->offset($offset)
					->limit($limit);
			}
			
			return $q;
		});
	}
	
	public function getGalleryPicture($id) {
		return $this->getBy(Tables::GALLERY_PICTURES, function($q) use ($id) {
			return $q
				->where('id', $id)
				->where('published', 1);
		});
	}
	
	public function getGalleryPicturePrev($pic) {
		return $this->getBy(Tables::GALLERY_PICTURES, function($q) use ($pic) {
			return $q
				->where('author_id', $pic['author_id'])
				->whereGt('created_at', $pic['created_at'])
				->where('published', 1)
				->orderByAsc('created_at');
		});
	}
	
	public function getGalleryPictureNext($pic) {
		return $this->getBy(Tables::GALLERY_PICTURES, function($q) use ($pic) {
			return $q
				->where('author_id', $pic['author_id'])
				->whereLt('created_at', $pic['created_at'])
				->where('published', 1)
				->orderByDesc('created_at');
		});
	}
	
	public function getUser($id) {
		return $this->get(Tables::USERS, $id);
	}

	public function getForumMemberByUser($user) {
		return $this->getBy(Tables::FORUM_MEMBERS, function($q) use ($user) {
			return $q->where('name', $user['forum_name'] ?? $user['login']);
		});
	}

	public function getForumMemberByName($name) {
		return $this->getBy(Tables::FORUM_MEMBERS, function($q) use ($name) {
			return $q->where('name', $name);
		});
	}
	
	// COMICS
	
	public function getComicPublisher($id) {
		return $this->get(Tables::COMIC_PUBLISHERS, $id);
	}

	public function getComicSeries($id = null) {
		return $id
			? $this->getBy(Tables::COMIC_SERIES, function($q) use ($id) {
				return $q
					->where('id', $id)
					->where('published', 1);
			})
			: $this->getMany(Tables::COMIC_SERIES, function($q) {
				return $q
					->where('published', 1);
			});
	}

	public function getComicSeriesByAlias($alias) {
		return $this->getBy(Tables::COMIC_SERIES, function($q) use ($alias) {
			return $q
				->where('alias', $alias)
				->where('published', 1);
		});
	}
	
	public function getComicStandalones() {
		return $this->getMany(Tables::COMIC_STANDALONES, function($q) {
			return $q
				->where('published', 1)
				->orderByDesc('issued_on');
		});
	}
	
	public function getComicStandalone($id) {
		return $this->getBy(Tables::COMIC_STANDALONES, function($q) use ($id) {
			return $q
				->where('id', $id)
				->where('published', 1);
		});
	}
	
	public function getComicStandaloneByAlias($alias) {
		return $this->getBy(Tables::COMIC_STANDALONES, function($q) use ($alias) {
			return $q
				->where('alias', $alias)
				->where('published', 1);
		});
	}
	
	public function getComicIssues($seriesId) {
		return $this->getMany(Tables::COMIC_ISSUES, function($q) use ($seriesId) {
			return $q
				->where('series_id', $seriesId)
				->where('published', 1)
				->orderByAsc('number');
		});
	}
	
	public function getComicIssue($seriesId, $number) {
		return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($seriesId, $number) {
			return $q
				->where('series_id', $seriesId)
				->where('number', $number)
				->where('published', 1);
		});
	}

	public function getComicIssuePages($comicId) {
		return $this->getMany(Tables::COMIC_PAGES, function($q) use ($comicId) {
			return $q
				->where('comic_issue_id', $comicId)
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicIssuePage($comicId, $number) {
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($comicId, $number) {
			return $q
				->where('comic_issue_id', $comicId)
				->where('number', $number)
				->where('published', 1);
		});
	}
	
	public function getComicStandalonePages($comicId) {
		return $this->getMany(Tables::COMIC_PAGES, function($q) use ($comicId) {
			return $q
				->where('comic_standalone_id', $comicId)
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicStandalonePage($comicId, $number) {
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($comicId, $number) {
			return $q
				->where('comic_standalone_id', $comicId)
				->where('number', $number)
				->where('published', 1);
		});
	}

	// generic	
	private function getComicPagePrev($page, $filter) {
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($page, $filter) {
			return $q
				->where($filter, $page[$filter])
				->whereLt('number', $page['number'])
				->where('published', 1)
				->orderByDesc('number');
		});
	}
	
	public function getComicPageNext($page, $filter) {
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($page, $filter) {
			return $q
				->where($filter, $page[$filter])
				->whereGt('number', $page['number'])
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicStandalonePagePrev($page) {
		return $this->getComicPagePrev($page, 'comic_standalone_id');
	}
	
	public function getComicStandalonePageNext($page) {
		return $this->getComicPageNext($page, 'comic_standalone_id');
	}
	
	public function getComicIssuePagePrev($comic, $page) {
		$prevPage = $this->getComicPagePrev($page, 'comic_issue_id');
		if ($prevPage) {
			$prevPage['comic'] = $comic;
		}
		else {
			$prevComic = $this->getComicIssuePrev($comic);
			if ($prevComic) {
				$prevComicPages = $this->getComicIssuePages($prevComic['id']);
				$prevPage = array_values(array_slice($prevComicPages, -1))[0];
				$prevPage['comic'] = $prevComic;
			}
		}
		
		return $prevPage;
	}
	
	public function getComicIssuePageNext($comic, $page) {
		$nextPage = $this->getComicPageNext($page, 'comic_issue_id');
		if ($nextPage) {
			$nextPage['comic'] = $comic;
		}
		else {
			$nextComic = $this->getComicIssueNext($comic);
			if ($nextComic) {
				$nextComicPages = $this->getComicIssuePages($nextComic['id']);
				$nextPage = $nextComicPages[0];
				$nextPage['comic'] = $nextComic;
			}
		}
		
		return $nextPage;
	}
	
	public function getComicIssuePrev($comic) {
		return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($comic) {
			return $q
				->where('series_id', $comic['series_id'])
				->whereLt('number', $comic['number'])
				->where('published', 1)
				->orderByDesc('number');
		});
	}
	
	public function getComicIssueNext($comic) {
		return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($comic) {
			return $q
				->where('series_id', $comic['series_id'])
				->whereGt('number', $comic['number'])
				->where('published', 1)
				->orderByAsc('number');
		});
	}
	
	// STREAMS
	
    function getStreams() {
    	$streams = $this->getMany(Tables::STREAMS, function($q) {
    		return $q
    			->where('published', 1)
    			->orderByDesc('remote_viewers');
    	});
    	
    	return $streams;
    }
    
    function getStreamByAlias($alias) {
    	return $this->getBy(Tables::STREAMS, function($q) use ($alias) {
    		return $q
    			->whereRaw('(stream_alias = ? or (stream_alias is null and stream_id = ?))', [ $alias, $alias ])
    			->where('published', 1);
    	});
    }
	
	public function saveStream($data) {
		$stream = $this->getObj(Tables::STREAMS, $data['id']);

        $stream->remote_viewers = $data['remote_viewers'];
        $stream->remote_title = $data['remote_title'];
        $stream->remote_game = $data['remote_game'];
        $stream->remote_status = $data['remote_status'];
        $stream->remote_logo = $data['remote_logo'];
        $stream->remote_online = $data['remote_online'];
		$stream->setExpr('remote_updated_at', 'now()');

		if ($data['remote_online'] == 1) {
			$stream->setExpr('remote_online_at', 'now()');
		}

		$stream->save();
	}
	
	public function getLastStreamStats($streamId) {
		return $this->getBy(Tables::STREAM_STATS, function($q) use ($streamId) {
			return $q
				->where('stream_id', $streamId)
				->orderByDesc('created_at');
		});
	}
	
	public function saveStreamStats($data) {
		$stats = $this->forTable(Tables::STREAM_STATS)->create();

		$stats->stream_id = $data['id'];
        $stats->remote_viewers = $data['remote_viewers'];
        $stats->remote_game = $data['remote_game'];
        $stats->remote_status = $data['remote_status'];

		$stats->save();
	}
	
	public function finishStreamStats($id) {
		$this->setField(Tables::STREAM_STATS, $id, 'finished_at', Util::now());
	}
}
