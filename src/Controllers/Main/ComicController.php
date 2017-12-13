<?php

namespace App\Controllers\Main;

class ComicController extends BaseController {
	private $comicsTitle;
	
	public function __construct($container) {
		parent::__construct($container);

		$this->comicsTitle = $this->getSettings('legacy.comics.title');
	}

	public function index($request, $response, $args) {
		$params = $this->buildParams([
			'sidebar' => [ 'stream' ],
			'params' => [
				'title' => $this->comicsTitle,
				'series' => $this->builder->buildSortedComicSeries(),
				'standalones' => $this->builder->buildSortedComicStandalones(),
			],
		]);
	
		return $this->view->render($response, 'main/comics/index.twig', $params);
	}

	public function series($request, $response, $args) {
		$alias = $args['alias'];
		
		$row = $this->db->getComicSeriesByAlias($alias);

		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$id = $row['id'];

		$series = $this->builder->buildComicSeries($row);

		$title = $series['name_ru'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}
	
		$comicRows = $this->db->getComicIssues($id);
		
		$comics = [];
		foreach ($comicRows as $comicRow) {
			$comics[] = $this->builder->buildComicIssue($comicRow, $series);
		}

		$params = $this->buildParams([
			'game' => $series['game'],
			'sidebar' => [ 'stream' ],
			'params' => [
				'series' => $series,
				'comics' => $comics,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
			],
		]);

		return $this->view->render($response, 'main/comics/series.twig', $params);
	}
	
	public function issue($request, $response, $args) {
		$alias = $args['alias'];
		$number = $args['number'];

		$seriesRow = $this->db->getComicSeriesByAlias($alias);

		if (!$seriesRow) {
			return $this->notFound($request, $response);
		}
		
		$series = $this->builder->buildComicSeries($seriesRow);
		
		$row = $this->db->getComicIssue($series['id'], $number);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicIssue($row, $series);
		
		$title = $series['name_ru'] . ' ' . $comic['number_str'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}
		
		$pageRows = $this->db->getComicIssuePages($comic['id']);
		foreach ($pageRows as $pageRow) {
			$pages[] = $this->builder->buildComicIssuePage($pageRow, $series, $comic);
		}
		
		if (isset($comic['prev'])) {
			$relPrev = $comic['prev']['page_url'];
		}
		
		if (isset($comic['next'])) {
			$relNext = $comic['next']['page_url'];
		}

		$params = $this->buildParams([
			'game' => $series['game'],
			'sidebar' => [ 'stream' ],
			'params' => [
				'series' => $series,
				'comic' => $comic,
				'pages' => $pages,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => $relPrev,
				'rel_next' => $relNext,
			],
		]);

		return $this->view->render($response, 'main/comics/issue.twig', $params);
	}
	
	public function standalone($request, $response, $args) {
		$alias = $args['alias'];

		$row = $this->db->getComicStandaloneByAlias($alias);

		if (!$row) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicStandalone($row);

		$title = $comic['name_ru'];
		if ($comic['name_en']) {
			$title .= ' (' . $comic['name_en'] . ')';
		}
		
		$pageRows = $this->db->getComicStandalonePages($comic['id']);
		foreach ($pageRows as $pageRow) {
			$pages[] = $this->builder->buildComicStandalonePage($pageRow, $comic);
		}

		$params = $this->buildParams([
			'game' => $comic['game'],
			'sidebar' => [ 'stream' ],
			'params' => [
				'comic' => $comic,
				'pages' => $pages,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
			],
		]);

		return $this->view->render($response, 'main/comics/standalone.twig', $params);
	}
	
	public function issuePage($request, $response, $args) {
		$alias = $args['alias'];
		$comicNumber = $args['number'];
		$pageNumber = $args['page'];

		$seriesRow = $this->db->getComicSeriesByAlias($alias);

		if (!$seriesRow) {
			return $this->notFound($request, $response);
		}
		
		$series = $this->builder->buildComicSeries($seriesRow);
		
		$comicRow = $this->db->getComicIssue($series['id'], $comicNumber);
		
		if (!$comicRow) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicIssue($comicRow, $series);

		$row = $this->db->getComicIssuePage($comic['id'], $pageNumber);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$page = $this->builder->buildComicIssuePage($row, $series, $comic);

		$title = $page['number_str'] . ' - ' . $series['name_ru'] . ' ' . $comic['number_str'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}
		
		if (isset($page['prev'])) {
			$relPrev = $page['prev']['page_url'];
		}
		
		if (isset($comic['next'])) {
			$relNext = $page['next']['page_url'];
		}

		$params = $this->buildParams([
			'game' => $series['game'],
			'params' => [
				'series' => $series,
				'comic' => $comic,
				'page' => $page,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => $relPrev,
				'rel_next' => $relNext,
			],
		]);

		return $this->view->render($response, 'main/comics/issue_page.twig', $params);
	}
	
	public function standalonePage($request, $response, $args) {
		$alias = $args['alias'];
		$pageNumber = $args['page'];

		$comicRow = $this->db->getComicStandaloneByAlias($alias);

		if (!$comicRow) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicStandalone($comicRow);

		$row = $this->db->getComicStandalonePage($comic['id'], $pageNumber);

		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$page = $this->builder->buildComicStandalonePage($row, $comic);

		$title = $page['number_str'] . ' - ' . $comic['name_ru'];
		if ($comic['name_en']) {
			$title .= ' (' . $comic['name_en'] . ')';
		}
		
		if (isset($page['prev'])) {
			$relPrev = $page['prev']['page_url'];
		}
		
		if (isset($page['next'])) {
			$relNext = $page['next']['page_url'];
		}

		$params = $this->buildParams([
			'game' => $comic['game'],
			'params' => [
				'comic' => $comic,
				'page' => $page,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => $relPrev,
				'rel_next' => $relNext,
			],
		]);

		return $this->view->render($response, 'main/comics/standalone_page.twig', $params);
	}
}
