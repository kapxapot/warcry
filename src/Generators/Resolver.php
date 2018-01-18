<?php

namespace App\Generators;

use Warcry\Contained;
use Warcry\Util\Util;

class Resolver extends Contained {
	private function buildClassName($name) {
		return __NAMESPACE__ . '\\' . $name;
	}
	
	public function resolveEntity($entity) {
		$pascalEntity = Util::toPascalCase($entity);
		$generatorClass = $this->buildClassName('Entities\\' . $pascalEntity);
		
		if (!class_exists($generatorClass)) {
			$generatorClass = $this->buildClassName('EntityGenerator');
		}
		
		return new $generatorClass($this->container, $entity);
	}
}
