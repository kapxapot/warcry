<?php

namespace App\Generators\Entities;

use Warcry\Util\Date;
use Warcry\Util\Strings;

use App\DB\Taggable;
use App\Generators\PublishableGenerator;

class Events extends PublishableGenerator {
	public function beforeSave($data, $id = null) {
		$data['cache'] = null;

		$data = $this->publishIfNeeded($data);		

		return $data;
	}

	public function afterSave($item, $data) {
		$this->updateTags($item);
	}
	
	private function updateTags($item) {
		$tags = Strings::toTags($item->tags);

		$this->db->saveTags(Taggable::EVENTS, $item->id, $tags);
	}

	public function afterDelete($item) {
		$this->db->deleteTags(Taggable::EVENTS, $item->id);
	}
}
