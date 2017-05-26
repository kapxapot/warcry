<?php

namespace App\Validation\Rules;

use Warcry\Validation\Rules\ContainerRule;

class ArticleNameCatAvailable extends ContainerRule {
	private $cat;
	private $id;
	
	public function __construct($cat = null, $id = null) {
		$this->cat = $cat;
		$this->id = $id;
	}

	public function validate($input) {
		$query = $this->container->db->forTable('articles')
			->where('name_en', $input);
		
		if ($this->cat) {
			$query = $query->where('cat', $this->cat);
		}
		else {
			$query = $query->where_null('cat');
		}
		
		if ($this->id) {
			$query = $query->where_not_equal('id', $this->id);
		}

		return $query->count() == 0;
	}
}
