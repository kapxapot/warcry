<?php

namespace App\Controllers\Main;

class GalleryController extends BaseController {
	private $galleryTitle;
	
	public function __construct($container) {
		parent::__construct($container);

		$this->galleryTitle = $this->getSettings('legacy.gallery.title');
	}

	public function index($request, $response, $args) {
		$authors = $this->builder->buildSortedGalleryAuthors();

		$galleryTopicId = $this->getSettings('legacy.gallery.forum_topic');

		$params = $this->buildParams([
			'sidebar' => [ 'stream' ],
			'params' => [
				'title' => $this->galleryTitle,
				'authors' => $authors,
				'forum_index' => $this->getSettings('legacy.forum.index'),
				'forum_topic' => $this->legacyRouter->forumTopic($galleryTopicId),
			],
		]);
	
		return $this->view->render($response, 'main/gallery/index.twig', $params);
	}

	public function author($request, $response, $args) {
		$alias = $args['alias'];

		$row = $this->db->getGalleryAuthorByAlias($alias);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$id = $row['id'];

		$author = $this->builder->buildGalleryAuthor($row);

		// paging...
		$totalCount = $author['count'];
		$picsPerPage = $this->getSettings('legacy.gallery.pics_per_page');
		$totalPages = ceil($totalCount / $picsPerPage);
	
		// determine page
		$page = $request->getQueryParam('page', 1);
		
		if (!is_numeric($page) || $page < 2) {
			$page = 1;
		}
	
		if ($page > $totalPages) {
			$page = $totalPages;
		}
		
		// paging itself
		$baseUrl = $author['page_url'];
		$paging = $this->builder->buildPaging($baseUrl, $totalPages, $page);
	
		if ($paging) {
			if (isset($paging['prev'])) {
				$relPrev = $paging['prev']['url'];
			}
			
			if (isset($paging['next'])) {
				$relNext = $paging['next']['url'];
			}
		}
	
		// pics
		$offset = ($page - 1) * $picsPerPage;
		
		$picRows = $this->db->getGalleryPictures($id, $offset, $picsPerPage);
		
		$pictures = [];
		
		foreach ($picRows as $picRow) {
			$pictures[] = $this->builder->buildGalleryPicture($picRow, $author);
		}

		$params = $this->buildParams([
			'sidebar' => [ 'stream' ],
			'params' => [
				'author' => $author,
				'pictures' => $pictures,
				'paging' => $paging,
				'title' => $author['name'],
				'gallery_title' => $this->galleryTitle,
				'disqus_url' => $this->legacyRouter->disqusGalleryAuthor($author),
				'disqus_id' => 'galleryauthor' . $id,
				'rel_prev' => $relPrev,
				'rel_next' => $relNext,
			],
		]);

		return $this->view->render($response, 'main/gallery/author.twig', $params);
	}
	
	public function picture($request, $response, $args) {
		$alias = $args['alias'];
		$id = $args['id'];

		$authorRow = $this->db->getGalleryAuthorByAlias($alias);
		
		if (!$authorRow) {
			return $this->notFound($request, $response);
		}
		
		$author = $this->builder->buildGalleryAuthor($authorRow);
		
		$row = $this->db->getGalleryPicture($id);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$picture = $this->builder->buildGalleryPicture($row);
	
		if (isset($picture['prev'])) {
			$relPrev = $picture['prev']['page_url'];
		}
		
		if (isset($picture['next'])) {
			$relNext = $picture['next']['page_url'];
		}

		$params = $this->buildParams([
			'params' => [
				'author' => $author,
				'picture' => $picture,
				'title' => $picture['comment'],
				'gallery_title' => $this->galleryTitle,
				'rel_prev' => $relPrev,
				'rel_next' => $relNext,
			],
		]);

		return $this->view->render($response, 'main/gallery/picture.twig', $params);
	}
}
