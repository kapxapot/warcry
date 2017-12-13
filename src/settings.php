<?php

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Arr;
use Warcry\File\File;

$path = __DIR__ . '/../settings/';

$settings = Yaml::parse(File::load($path . 'general.yml'));

foreach ($settings['modules'] as $module) {
	$settings[$module] = Yaml::parse(File::load("{$path}{$module}.yml"));
}

$settings['db'] = [
	'host' => getenv('DB_HOST'),
	'database' => getenv('DB_DATABASE'),
	'user' => getenv('DB_USER'),
	'password' => getenv('DB_PASSWORD'),
];

$settings['twitch']['client_id'] = getenv('TWITCH_CLIENT_ID');
$settings['telegram']['bot_token'] = getenv('TELEGRAM_BOT_TOKEN');

if (array_key_exists('tables', $settings)) {
	foreach ($settings['tables'] as $table => $tableSettings) {
		$public = $tableSettings['public'] ?? [];
		$private = $tableSettings['private'] ?? [];
		
		$fields = array_unique(array_merge($private, $public));
		
		Arr::set($settings, "tables.{$table}.fields", $fields);
	}
	
	// flatten attributes
	foreach ($settings['entities'] as $entity => $entitySettings) {
		Arr::set($settings, "entities.{$entity}.alias", $entity);
		
		foreach ($entitySettings['columns'] as $column => $columnSettings) {
			foreach ($columnSettings['attributes'] ?? [] as $attr) {
				Arr::set($settings, "entities.{$entity}.columns.{$column}.{$attr}", 'true');
			}
		}
	}

	// count sort index
	foreach ($settings['entities'] as $entity => $entitySettings) {
		$sort = Arr::get($settings, "tables.{$entity}.sort");
		
		$count = 0;
		foreach ($entitySettings['columns'] as $column => $columnSettings) {
			if ($column == $sort) {
				Arr::set($settings, "tables.{$entity}.sort_index", $count);
				break;
			}
			elseif (!array_key_exists('hidden', $columnSettings)) {
				$count++;
			}
		}
	}
	
	// build menu
	/*$entityMenu = [];
	foreach ($settings['entities'] as $entity => $entitySettings) {
		if (array_key_exists('menu_index', $entitySettings)) {
			$entityMenu[$entitySettings['menu_index']] = [
				'entity' => $entity,
				'title' => $entitySettings['title'],
			];
		}
	}

	ksort($entityMenu);
	
	$flatEntityMenu = [];
	foreach ($entityMenu as $entry) {
		$flatEntityMenu[$entry['entity']] = $entry['title'];
	}
	
	Arr::set($settings, "view_globals.entity_menu", $flatEntityMenu);*/
}

return [ 'settings' => $settings ];
