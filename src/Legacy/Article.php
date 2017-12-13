<?php

namespace App\Legacy;

use Warcry\Contained;

class Article extends Contained {
	public $id;

	public $data;
	
	public $breadcrumbs;
	public $contents;
	public $text;

	private $parser;
	private $decorator;
	
	public function __construct($container, $id, $cat, $rebuild = false) {
		parent::__construct($container);
		
		$this->parser = $this->legacyArticleParser; // from container
		$this->decorator = $this->legacyDecorator; // from container

		$id = $this->parser->toSpaces($id);
		$cat = $this->parser->toSpaces($cat);

		$this->data = $this->db->getArticle($id, $cat);
		
		if ($this->data) {
	        $this->id = $this->data['id'];
	        
	        $text = $this->data['text'];
	
			$this->breadcrumbs = $this->buildBreadcrumbs();
	
			if (!$rebuild && (strlen($this->data['cache']) > 0)) {
				$this->text = $this->data['cache'];
				$this->contents = json_decode($this->data['contents_cache']);
			}
			elseif (strlen($text) > 0) {
				$result = (preg_match("/\s*\<article\>/", $text))
					? $this->parser->parseXML($text)
					: $this->parser->parseBB($text);

				$this->text = $result['text'];
				$this->contents = $this->buildContents($result['contents']);
				
				$this->db->saveArticleCache($this->id, $this->text);
				$this->db->saveArticleContentsCache($this->id, json_encode($this->contents));
			}
		}
	}

	private function buildBreadcrumbs() {
		$curId = $this->data['parent_id'];
		
		while ($curId > 0) {
			$article = $this->db->getArticle($curId);
			
			if (!$article) {
				break;
			}

			if (!$article['no_breadcrumb']) {
				$linkArray[] = $this->builder->buildArticleLink($article);
			}

			$curId = $article['parent_id'];
		}
		
		return array_reverse($linkArray);
	}

	private function buildContents($contents) {
		foreach ($contents as $linkData) {
			$label = $linkData['label'];
			$text = $linkData['text'];
			$level = $linkData['level'];

			$text = preg_replace('/_/', '.', $label) . '. ' . $text;

			$link = $this->decorator->url("#" . $label, $text);
			$link = $this->decorator->padLeft($link, 20 * ($level - 1));

			$list[] = $link;
		}

		return $list;
	}
}
