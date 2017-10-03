<?php

namespace App\Route\Generators;

use Warcry\Contained;
use Warcry\Exceptions\ValidationException;

use App\Validation\ValidationRules;

class EntityGenerator extends Contained {
	protected $entity;
	protected $rules;

	public function __construct($container, $entity) {
		parent::__construct($container);
		
		$this->entity = $entity;
		$this->rules = new ValidationRules($container);
	}
	
	protected function rule($name, $optional = false) {
		return $this->rules->get($name, $optional);
	}
	
	protected function optional($name) {
		return $this->rule($name, true);
	}

	protected function getRules($data, $id = null) {
		return [];
	}
	
	protected function getOptions() {
		return [];
	}
	
	public function afterLoad($item) {
		return $item;
	}
	
	public function beforeSave($data, $id = null) {
		return $data;
	}
	
	public function afterSave($item, $data) {
	}
	
	public function afterDelete($item) {
	}
	
	public function getAdminParams($params, $args) {
		return $params;
	}
	
	public function validate($request, $data, $id = null) {
		$rules = $this->getRules($data, $id);
		$validation = $this->validator->validate($request, $rules);
		
		if ($validation->failed()) {
			throw new ValidationException($validation->errors);
		}
	}
	
	public function generateAPIRoutes($app, $access) {
		$this->generateGetAllRoute($app, $access);
		
		$api = $this->getSettings("tables.{$this->entity}.api");
		
		if ($api == "full") {
			$this->generateCRUDRoutes($app, $access);
		}
		
		return $this;
	}
	
	public function generateGetAllRoute($app, $access) {
		$alias = $this->entity;
		$provider = $this;
		$options = $this->getOptions();

		$uri = $options['uri'] ?? $alias;

		$app->get('/' . $uri, function($request, $response, $args) use ($alias, $provider, $options) {
			$options['args'] = $args;
			$options['params'] = $request->getParams();
			
			return $this->db->jsonMany($response, $alias, $provider, $options);
		})->add($access($alias, 'api_read'));
		
		return $this;
	}
	
	public function generateCRUDRoutes($app, $access) {
		$alias = $this->entity;
		$table = $this->entity;
		$provider = $this;
		
		$shortPath = '/' . $alias;
		$fullPath = '/' . $alias . '/{id:\d+}';

		$get = $app->get($fullPath, function ($request, $response, $args) use ($table, $provider) {
			return $this->db->get($response, $table, $args['id'], $provider);
		});
		
		$post = $app->post($shortPath, function ($request, $response, $args) use ($table, $provider) {
			return $this->db->create($request, $response, $table, $provider);
		});
		
		$put = $app->put($fullPath, function ($request, $response, $args) use ($table, $provider) {
			return $this->db->update($request, $response, $table, $args['id'], $provider);
		});
		
		$delete = $app->delete($fullPath, function ($request, $response, $args) use ($table, $provider) {
			return $this->db->delete($response, $table, $args['id'], $provider);
		});
		
		if ($access) {
			$get->add($access($table, 'api_read'));
			$post->add($access($table, 'api_create'));
			$put->add($access($table, 'api_edit'));
			$delete->add($access($table, 'api_delete'));
		}
		
		return $this;
	}

	public function generateAdminPageRoute($app, $access) {
		$alias = $this->entity;
		$settings = $this->getSettings();
		$provider = $this;
		$options = $this->getOptions();

		$params = $settings['entities'][$alias];

		$uri = $options['admin_uri'] ?? $options['uri'] ?? $alias;

		$app->get('/' . $uri, function($request, $response, $args) use ($params, $provider, $options) {
			$templateName = isset($options['admin_template'])
				? ('entities/' . $options['admin_template'])
				: 'entity';

			$params = $provider->getAdminParams($params, $args);

			return $this->view->render($response, $templateName . '.twig', $params);
		})->add($access($alias, 'read_own', 'admin.index'))->setName('admin.' . $alias);
		
		return $this;
	}
}
