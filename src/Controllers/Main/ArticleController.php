<?php

namespace App\Controllers\Main;

use App\Legacy\Article;

class ArticleController extends BaseController {
	public function item($request, $response, $args) {
		$id = $args['id'];
		$cat = $args['cat'];

		$rebuild = $request->getQueryParam('rebuild', false);

		$article = new Article($this->container, $id, $cat, $rebuild);

		if (!$article->data) {
			return $this->notFound($request, $response);
		}

		$id = $article->id;

		$article = $this->builder->buildArticle($article);

		$params = $this->buildParams([
			'game' => $article['game'],
			'sidebar' => [ 'stream', 'create.articles', 'events', 'articles' ],
			'article_id' => $id,
			'params' => [
				'disqus_url' => $this->legacyRouter->disqusArticle($article),
				'disqus_id' => 'article' . $id . $cat,
				'article' => $article,
				'title' => $article['title'],
				'page_description' => $article['description'],
			],
		]);

		return $this->view->render($response, 'main/articles/item.twig', $params);
	}
	
	protected function getArticle($args) {
		$id = $args['id'];
		$cat = $args['cat'];

		$id = $this->legacyArticleParser->toSpaces($id);
		$cat = $this->legacyArticleParser->toSpaces($cat);

		return $this->db->getArticle($id, $cat);
	}

	/*public function convert($request, $response, $args) {
		$article = $this->getArticle($args);

		if (!$article) {
			return $this->notFound($request, $response);
		}

		$text = $article['text'];
		
		return $this->legacyArticleParser->convertArticleFromXml($text);
	}*/

	public function source($request, $response, $args) {
		$article = $this->getArticle($args);

		if (!$article) {
			return $this->notFound($request, $response);
		}

		return $article['text'];
	}
}
