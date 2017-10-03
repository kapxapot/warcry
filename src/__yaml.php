<?php

if (isset($_GET['debug']) or true) {
	error_reporting(E_ALL & ~E_NOTICE);
	ini_set("display_errors", 1);
}

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$array = [
	'games' => [
		'entity_name_gen' => 'игры',
		'entity_name_accus' => 'игру',
		'title' => 'Игры',
		'sort' => 'position',
	]
];

$yaml = Yaml::dump($array);

//file_put_contents('../settings/entities.yml', $yaml);

$value = Yaml::parse(file_get_contents('../settings/access.yml'));

function flattenActions($tree, $path = [], $flat = []) {
	foreach ($tree as $node) {
		if (is_array($node)) {
			foreach ($node as $nodeTitle => $nodeTree) {
				$pathCopy = $path;
				$pathCopy[] = $nodeTitle;
				$flat[$nodeTitle] = $pathCopy;
	
				$flat = flattenActions($nodeTree, $pathCopy, $flat);
			}
		}
		else {
			$pathCopy = $path;
			$pathCopy[] = $node;
			$flat[$node] = $pathCopy;
		}
	}
	
	return $flat;
}

$flat = flattenActions($value['actions']);

dd($flat);
