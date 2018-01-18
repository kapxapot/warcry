<?php

namespace App\DB;

use Warcry\ORM\Idiorm\Helper;

class TableHelper extends Helper {
	private $table;

	public function __construct($c, $table) {
		parent::__construct($c);
		
		$this->table = $table;
	}
	
	public function getTableRights() {
		return $this->access->getAllRights($this->table);
	}
	
	public function getRights($item) {
		$item = $this->toArray($item);
		
		$noOwner = !isset($item['created_by']);
		$own = $this->auth->isOwnerOf($item);
		$can = $this->getTableRights();

		$can['read'] = $noOwner || $can['read'] || ($own && $can['read_own']);
		$can['edit'] = $can['edit'] || ($own && $can['edit_own']);
		$can['delete'] = $can['delete'] || ($own && $can['delete_own']);
		
		return $can;
	}
	
	public function addRights($item) {
		if ($item) {
			$access = $this->getRights($item);
	
			$item['access']['edit'] = $access['edit'];
			$item['access']['delete'] = $access['delete'];
		}

		return $item;
	}
	
	public function canRead($item) {
		$access = $this->getRights($item);
		return $access['read'];
	}
}
