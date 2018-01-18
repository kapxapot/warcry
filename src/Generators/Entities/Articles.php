<?php

namespace App\Generators\Entities;

use App\Generators\PublishableGenerator;

class Articles extends PublishableGenerator {
	public function getRules($data, $id = null) {
		$rules = [
			'name_ru' => $this->rule('text'),//->regex($cyr("'\(\):\-\.\|,\?!—«»"));
		];

		if (array_key_exists('name_en', $data) && array_key_exists('cat', $data)) {
			$rules['name_en'] = $this->rule('text')
				->regex($this->rules->lat("':\-"))
				->articleNameCatAvailable($data['cat'], $id);
		}
		
		return $rules;
	}
	
	public function getOptions() {
		return [
			'exclude' => [ 'text' ],
			'admin_template' => 'article',
		];
	}
	
	public function beforeSave($data, $id = null) {
		$data['cache'] = null;
		$data['contents_cache'] = null;
		
		$data = $this->publishIfNeeded($data);		
		
		return $data;
	}

	public function afterSave($item, $data) {
		$this->notify($item, $data);
	}

	private function notify($item, $data) {
		if ($this->isJustPublished($item, $data) && $item->announce == 1) {
			if ($item->cat) {
				$catObj = $this->db->getCat($item->cat);
				if ($catObj) {
					$catName = $catObj['name_en'];
				}
			}
			
			$url = $this->legacyRouter->article($item->name_en, $catName);
			$url = $this->legacyRouter->abs($url);
			
			$this->telegram->sendMessage('warcry', "Опубликована статья:
<a href=\"{$url}\">{$item->name_ru}</a>");
		}
	}
}
