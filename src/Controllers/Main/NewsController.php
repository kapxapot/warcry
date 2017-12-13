<?php

namespace App\Controllers\Main;

use Warcry\Util\Sort;

class NewsController extends BaseController {
	public function index($request, $response, $args) {
		if ($args['game']) {
			$filterByGame = $this->db->getGameByAlias($args['game']);
			
			if (!$filterByGame) {
				return $this->notFound($request, $response);
			}
		}

		$offset = $request->getQueryParam('offset', 0);
		$limit = $request->getQueryParam('limit', $this->getSettings('legacy.news_limit'));

		$news = $this->builder->buildAllNews($filterByGame, $offset, $limit);

		$params = $this->buildParams([
			'game' => $filterByGame,
			'sidebar' => [ 'stream', 'forum', 'articles' ],
			'params' => [
				//'news_index' => $this->legacyRouter->forumNewsIndex(),
				'news' => $news,
			],
		]);
	
		return $this->view->render($response, 'main/news/index.twig', $params);
	}

	public function item($request, $response, $args) {
		$id = $args['id'];
		$rebuild = $request->getQueryParam('rebuild', false);

		$forumNewsRow = $this->db->getForumNews($id);
		$newsRow = $this->db->getNews($id);
		
		if (!$forumNewsRow && !$newsRow) {
			return $this->notFound($request, $response);
		}

		$news = $forumNewsRow
			? $this->builder->buildForumNews($forumNewsRow, true)
			: $this->builder->buildNews($newsRow, true, $rebuild);

		$params = $this->buildParams([
			'game' => $news['game'],
			'sidebar' => [ 'stream', 'news', 'forum' ],
			'news_id' => $id,
			'params' => [
				'disqus_url' => $this->legacyRouter->disqusNews($id),
				'disqus_id' => 'news' . $id,
				'news_item' => $news,
				'title' => $news['title'],
				'page_description' => $news['description'],
			],
		]);

		return $this->view->render($response, 'main/news/item.twig', $params);
	}
	
	public function rss($request, $response, $args) {
		$game = null;
		$offset = 0;
		$limit = $this->getSettings('legacy.rss_limit');
		
		$news = $this->builder->buildAllNews($filterByGame, $offset, $limit);

		$fileName = __DIR__ . $this->getSettings('folders.rss_cache') . 'rss.xml';

		$settings = $this->getSettings('view_globals');
		
		$siteUrl = $settings['site_url'];
		$siteName = $settings['site_name'];
		$siteDescription = $settings['site_description'];
		$teamMail = $settings['team_mail'];
		
		$rss = new \UniversalFeedCreator();
		$rss->useCached($fileName, 300);
		$rss->title = $siteName;
		$rss->description = $siteDescription;
		$rss->link = $siteUrl;
		$rss->syndicationURL = $siteUrl . '/rss';
		$rss->encoding = "utf-8";
		$rss->language = 'ru';
		$rss->copyright = $siteName;
		$rss->webmaster = $teamMail;
		$rss->ttl = 300;
		
		$image = new \FeedImage();
		$image->title = $siteName . " logo";
		$image->url = $siteUrl . '/images/text_logo_4.png';
		$image->link = $siteUrl;
		$image->description = $siteDescription;
		$rss->image = $image;

		foreach ($news as $n) {
			$item = new \FeedItem();
			$item->title = $n['title'];
			$item->link = $this->legacyRouter->n($n['tid']);
			$item->description = $n['text'];
			$item->date = $n['pub_date'];
			$item->author = $n['starter_name'];
			$item->category = array_map(function($t) {
				return $t['text'];
			}, $n['tags']);
			
			$rss->addItem($item);
		}
		
		$rss->saveFeed("RSS2.0", $fileName, true);
	}
}
