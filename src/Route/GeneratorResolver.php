<?php

namespace App\Route;

use Warcry\Contained;
use Warcry\Util\Util;

class GeneratorResolver extends Contained {
	private function buildClassName($name) {
		return __NAMESPACE__ . '\\Generators\\' . Util::toPascalCase($name) . 'Generator';
	}
	
	public function resolve($entity) {
		$generatorClass = $this->buildClassName($entity);
		
		if (!class_exists($generatorClass)) {
			$generatorClass = $this->buildClassName('entity');
		}
		
		return new $generatorClass($this->container, $entity);
	}
}
