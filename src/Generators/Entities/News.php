<?php

namespace App\Generators\Entities;

use Warcry\Util\Strings;

use App\DB\Taggable;
use App\Generators\PublishableGenerator;

class News extends PublishableGenerator {
	public function beforeSave($data, $id = null) {
		$data['cache'] = null;

		$data = $this->publishIfNeeded($data);		

		return $data;
	}

	public function afterSave($item, $data) {
		$this->updateTags($item);
		$this->notify($item, $data);
	}
	
	private function updateTags($item) {
		$tags = Strings::toTags($item->tags);

		$this->db->saveTags(Taggable::NEWS, $item->id, $tags);
	}

	private function notify($item, $data) {
		if ($this->isJustPublished($item, $data)) {
			$url = $this->legacyRouter->news($item->id);
			$url = $this->legacyRouter->abs($url);
			
			$this->telegram->sendMessage('warcry', "Опубликована новость:
<a href=\"{$url}\">{$item->title}</a>");
		}
	}

	public function afterDelete($item) {
		$this->db->deleteTags(Taggable::NEWS, $item->id);
	}
}
