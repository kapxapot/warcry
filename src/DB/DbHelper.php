<?php

namespace App\DB;

use Warcry\Util\Util;
use Warcry\ORM\Idiorm\DbHelper as DbHelperBase;

class DbHelper extends DbHelperBase {
	private function getTableHelper($table) {
		return new TableHelper($this->container, $table);
	}
	
	protected function can($table, $rights, $item = null) {
		$tableHelper = $this->getTableHelper($table);
		
		$access = $item
			? $tableHelper->getRights($item)
			: $tableHelper->getTableRights();

		return $access[$rights];
	}

	private function addUserNames($item) {
		if (isset($item['created_by'])) {
			$created = $this->getUser($item['created_by']);
			if ($created !== null) {
				$item['created_by_name'] = $created['login'];
			}
		}

		if (isset($item['updated_by'])) {
			$updated = $this->getUser($item['updated_by']);
			if ($updated !== null) {
				$item['updated_by_name'] = $updated['login'];
			}
		}
		
		return $item;
	}

	public function getMany($table, $provider, $options = []) {
		$exclude = $options['exclude'] ?? null;

		$items = $this->selectMany($table, $exclude);
		
		if (isset($options['filter'])) {
			$items = $this->filterBy($items, $options['filter'], $options['args']);
		}

		$settings = $this->tables[$table];
		
		if (isset($settings['sort'])) {
			$sortBy = $settings['sort'];
			$reverse = isset($settings['reverse']);
			$items = $reverse
				? $items->orderByDesc($sortBy)
				: $items->orderByAsc($sortBy);
		}
		
		$array = $items->findArray();
		
		$tableHelper = $this->getTableHelper($table);

		$array = array_filter($array, array($tableHelper, 'canRead'));
		$array = array_map(array($provider, 'afterLoad'), $array);
		$array = array_map(array($this, 'addUserNames'), $array);
		$array = array_map(array($tableHelper, 'addRights'), $array);

		return array_values($array);
	}

	protected function beforeValidate($request, $table, $data, $id = null) {
		// unset
		$canPublish = $this->can($table, 'publish');
		
		if (isset($data['published']) && !$canPublish) {
			unset($data['published']);
		}

		if (isset($data['password'])) {
			$password = $data['password'];
			if (strlen($password) > 0) {
				$data['password'] = Util::encodePassword($password);
			}
			else {
				unset($data['password']);
			}
		}

		// dirty
		/*if ($this->hasField($table, 'created_at') && !$id) {
			$data['created_at'] = Util::now();
		}*/

		if ($this->hasField($table, 'updated_at')) {
			$data['updated_at'] = Util::now();
		}

		$user = $this->auth->getUser();
		if ($this->hasField($table, 'created_by') && !$id) {
			$data['created_by'] = $user->id;
		}
		
		if ($this->hasField($table, 'updated_by')) {
			$data['updated_by'] = $user->id;
		}

		return $data;
	}

	public function getUser($id) {
		return $this->getEntityById('users', $id);
	}
}
