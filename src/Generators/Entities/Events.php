<?php

namespace App\Generators\Entities;

use Warcry\Util\Date;
use Warcry\Util\Strings;

use App\DB\Taggable;
use App\Generators\EntityGenerator;

class Events extends EntityGenerator {
	public function beforeSave($data, $id = null) {
		if (isset($data['published']) && $data['published'] == 1 && !isset($data['published_at'])) {
			$data['published_at'] = Date::now();
		}

		return $data;
	}

	public function afterSave($item, $data) {
		$this->updateTags($item);
		//$this->notify($item, $data);
	}
	
	private function updateTags($item) {
		$tags = Strings::toTags($item->tags);

		$this->db->saveTags(Taggable::EVENTS, $item->id, $tags);
	}

	/*private function notify($item, $data) {
		if (!isset($data['published_at']) && isset($item->published_at) && Date::happened($item->published_at)) {
			$url = $this->legacyRouter->news($item->id);
			$url = $this->legacyRouter->abs($url);
			
			$this->telegram->sendMessage('warcry', "Опубликована новость:
<a href=\"{$url}\">{$item->title}</a>");
		}
	}*/

	public function afterDelete($item) {
		$this->db->deleteTags(Taggable::EVENTS, $item->id);
	}
}
