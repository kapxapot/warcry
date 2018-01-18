<?php

namespace App\Generators\Entities;

use App\Generators\EntityGenerator;

class MenuItems extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'link' => $this->rule('url'),
			'text' => $this->rule('text'),
			'position' => $this->rule('posInt'),
		];
	}
	
	public function getOptions() {
		return [
			'uri' => 'menus/{id:\d+}/menu_items',
			'filter' => 'section_id',
		];
	}
	
	public function getAdminParams($args) {
		$params = parent::getAdminParams($args);

		$menuId = $args['id'];
		$menu = $this->db->getEntityById('menus', $menuId);
		$game = $this->db->getEntityById('games', $menu['game_id']);
		
		$params['source'] = "menus/{$menuId}/menu_items";
		$params['breadcrumbs'] = [
			[ 'text' => 'Меню', 'link' => $this->router->pathFor('admin.entities.menus') ],
			[ 'text' => $game ? $game['name'] : '(нет игры)' ],
			[ 'text' => $menu['text'] ],
			[ 'text' => 'Элементы меню' ],
		];
		
		$params['hidden'] = [
			'section_id' => $menuId,
		];
		
		return $params;
	}
}
