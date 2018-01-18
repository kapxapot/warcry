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
			'sidebar' => [ 'stream', 'create.articles', 'articles' ],
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
}
