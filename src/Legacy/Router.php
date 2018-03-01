<?php

namespace App\Legacy;

use Warcry\Contained;
use Warcry\Util\Cases;
use Warcry\Util\Sort;

use App\Gallery\Gallery;

class Router extends Contained {
	private $articleParser;

	public function __construct($container) {
		parent::__construct($container);

		$this->articleParser = $this->legacyArticleParser; // from container
	}

	// urls
	private function forumUrl($url) {
		return $this->getSettings('legacy.forum.page') . '?' . $url;
	}
	
	public function abs($url) {
		if (substr($url, 0, 1) !== '/') {
			$url = '/' . $url;
		}
		
		return $this->getSettings('view_globals.site_url') . $url;
	}

	// site
	public function article($id = null, $cat = null) {
		$params = [ 'id' => $this->articleParser->fromSpaces($id) ];
		
		if ($cat) {
			$params['cat'] = $this->articleParser->fromSpaces($cat);
		}

		return $this->router->pathFor('main.article', $params);
	}

	public function news($id = null) {
		return $this->router->pathFor('main.news', [ 'id' => $id ]);
	}

	public function event($id = null) {
		return $this->router->pathFor('main.event', [ 'id' => $id ]);
	}

	public function tag($tag = null, $tab = null) {
		$tag = $this->articleParser->fromSpaces($tag, '+');
		$url = $this->router->pathFor('main.tag', [ 'tag' => $tag ]);
		
		if ($tab) {
			$url .= '#/' . $tab;
		}
		
		return $url;
	}

	public function n($id) {
		return $this->abs('n/' . $id);
	}

	public function game($game) {
		$params = [];
		
		if (!$game['default']) {
			$params['game'] = $game['alias'];
		}
		
		return $this->router->pathFor('main.index', $params);
	}
	
	// disqus
	public function disqusNews($id) {
		return $this->abs($this->news($id));
	}
	
	public function disqusArticle($article) {
		$id = $article['name_en'];
		
		if ($article['cat'] != null) {
			$cat = $article['cat']['name_en'];
		}

		return $this->abs($this->article($id, $cat));
	}
	
	public function disqusGalleryAuthor($author) {
		return $this->abs($this->galleryAuthor($author));
	}

	public function disqusRecipes($skill) {
		return $this->abs($this->recipes($skill));
	}

	public function disqusRecipe($id) {
		return $this->abs($this->recipe($id));
	}

	// forum
	public function forumTag($text) {
		return $this->forumUrl('app=core&module=search&do=search&search_tags=' . urlencode($text) . '&search_app=forums');
	}
	
	public function forumUser($id) {
		return $this->forumUrl('showuser=' . $id);
	}
	
	public function forumNewsIndex() {
		$index = $this->getSettings('legacy.forum.news_index');
	
		return $this->forumUrl('showforum=' . $index);
	}
	
	public function forumTopic($id, $new = false) {
		$appendix = $new ? '&view=getnewpost' : '';
		
		return $this->forumUrl('showtopic=' . $id . $appendix);
	}
	
	public function forumUpload($name) {
		return $this->getSettings('legacy.forum.index') . '/uploads/' . $name;
	}
	
	// gallery
	public function galleryAuthor($alias) {
		return $this->router->pathFor('main.gallery.author', [ 'alias' => $alias ]);
	}

	public function getExtension($type) {
		return $this->gallery->getExtension($type ?? 'jpeg');		
	}
	
	public function galleryPictureImg($picture) {
		$ext = $this->getExtension($picture['picture_type']);
		return $this->getSettings('legacy.gallery.pictures_index') . '/' . $picture['id'] . '.' . $ext;
	}
	
	public function galleryThumbImg($picture) {
		$ext = $this->getExtension($picture['thumb_type']);
		return $this->getSettings('legacy.gallery.thumbs_index') . '/' . $picture['id'] . '.' . $ext;
	}
	
	public function galleryPicture($alias, $id) {
		return $this->router->pathFor('main.gallery.picture', [ 'alias' => $alias, 'id' => $id ]);
	}
	
	// streams
	public function stream($alias = null) {
		return $this->router->pathFor('main.stream', [ 'alias' => $alias ]);
	}
	
	// paging
	function page($base, $page) {
		$delim = strpos($base, '?') !== false ? '&' : '?';
		return $base . ($page == 1 ? '' : "{$delim}page={$page}");
	}
	
	// comics
	public function comicSeries($alias) {
		return $this->router->pathFor('main.comics.series', [ 'alias' => $alias ]);
	}

	public function comicIssue($alias, $comicNumber) {
		return $this->router->pathFor('main.comics.issue', [ 'alias' => $alias, 'number' => $comicNumber ]);
	}

	public function comicIssuePage($alias, $comicNumber, $pageNumber) {
		return $this->router->pathFor('main.comics.issue.page', [
			'alias' => $alias,
			'number' => $comicNumber,
			'page' => $pageNumber,
		]);
	}

	public function comicStandalone($alias) {
		return $this->router->pathFor('main.comics.standalone', [ 'alias' => $alias ]);
	}

	public function comicStandalonePage($alias, $pageNumber) {
		return $this->router->pathFor('main.comics.standalone.page', [
			'alias' => $alias,
			'page' => $pageNumber,
		]);
	}

	public function comicPageImg($page) {
		$ext = $this->getExtension($page['type']);
		return $this->getSettings('legacy.comics.pages_index') . '/' . $page['id'] . '.' . $ext;
	}
	
	public function comicThumbImg($page) {
		$ext = $this->getExtension($page['type']);
		return $this->getSettings('legacy.comics.thumbs_index') . '/' . $page['id'] . '.' . $ext;
	}
	
	// recipes
	public function recipes($skill = null) {
		$params = [];
		
		if ($skill) {
			$params['skill'] = $skill['alias'];
		}
		
		return $this->router->pathFor('main.recipes', $params);
	}
	
	public function recipe($id) {
		return $this->router->pathFor('main.recipe', [ 'id' => $id ]);
	}
	
	// wowhead
	public function wowheadIcon($icon) {
		$icon = strtolower($icon);
		return "//static.wowhead.com/images/wow/icons/medium/{$icon}.jpg";
	}
	
	private function wowheadUrl($params) {
		return $this->getSettings('legacy.webdb_link') . $params;
	}
	
	private function wowheadUrlRu($params) {
		return $this->getSettings('legacy.webdb_ru_link') . $params;
	}
	
	public function wowheadSpellRu($id) {
		return $this->wowheadUrlRu('spell=' . $id);
	}
	
	public function wowheadItemRu($id) {
		return $this->wowheadUrlRu('item=' . $id);
	}
	
	public function wowheadItem($id) {
		return $this->wowheadUrl('item=' . $id);
	}
	
	public function wowheadItemXml($id) {
		return $this->wowheadItem($id) . '&xml';
	}
	
	public function wowheadItemRuXml($id) {
		return $this->wowheadItemRu($id) . '&xml';
	}
	
	// twitch
	public function twitchImg($id) {
		return "//static-cdn.jtvnw.net/previews-ttv/live_user_{$id}-320x180.jpg";
	}
	
	public function twitchLargeImg($id) {
		return "//static-cdn.jtvnw.net/previews-ttv/live_user_{$id}-640x360.jpg";
	}
	
	public function twitch($id) {
		return 'http://twitch.tv/' . $id;
	}
}
