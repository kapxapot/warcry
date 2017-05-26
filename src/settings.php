<?php

use Symfony\Component\Yaml\Yaml;

$path = __DIR__ . '/../settings/';

$settings = Yaml::parse(file_get_contents($path . 'general.yml'));

foreach ($settings['modules'] as $module) {
	$settings[$module] = Yaml::parse(file_get_contents("{$path}{$module}.yml"));
}

if (isset($settings['tables'])) {
	foreach ($settings['tables'] as &$table) {
		$public = isset($table['public']) ? $table['public'] : [];
		$private = isset($table['private']) ? $table['private'] : [];
		
		$table['fields'] = array_unique(array_merge($private, $public));
	}
	
	if (isset($settings['entities'])) {
		foreach ($settings['entities'] as $entity => $entitySettings) {
			$sort = $settings['tables'][$entity]['sort'];
			
			$count = 0;
			if (isset($entitySettings['columns'])) {
				foreach ($entitySettings['columns'] as $column => $columnSettings) {
					if ($column == $sort) {
						$settings['tables'][$entity]['sort_index'] = $count;
						break;
					}
					elseif (!isset($columnSettings['hidden'])) {
						$count++;
					}
				}
			}
		}
	}
}

return [ 'settings' => $settings ];
