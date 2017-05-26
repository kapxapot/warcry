<?php

use App\Auth\Auth;
use App\Auth\Captcha;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\TokenAuthMiddleware;
use App\Middleware\AccessMiddleware;

use Warcry\Exceptions\NotFoundException;

$access = function($entity, $action, $redirect = null) use ($container) {
	return new AccessMiddleware($container, $entity, $action, $redirect);
};

$settings = $container->get('settings');

$app->get('/', function($request, $response, $args) {
	//return $response->withRedirect($this->router->pathFor('admin.index'));
})->setName('home');

$app->group('/api/v1', function() use ($container, $access) {
	$db = $container->db;

	// generic
	$get = function($app, $alias, $options = []) use ($access) {
		$uri = isset($options['uri'])
			? $options['uri']
			: $alias;

		$app->get('/' . $uri, function($request, $response, $args) use ($alias, $options) {
			$options['args'] = $args;
			$options['params'] = $request->getParams();
			return $this->db->jsonMany($response, $alias, $options);
		})->add($access($alias, 'api_read'));
	};

	$crud = function($app, $alias, $options = []) use ($db, $access) {
		$db->crud($app, $alias, $access, $options);
	};

	$filterBy = function($field) {
		return function($items, $args) use ($field) {
			return $items->where($field, $args['id']);
		};
	};

	$get($this, 'articles', [ 'exclude' => [ 'text' ]]);
	$get($this, 'article_categories');
	
	$get($this, 'gallery_authors');/*, [ 'mutator' => function($item) use ($db) {
		$item['pics_count'] = $db->forTable('gallery_pictures')
			->where('author_id', $item['id'])
			->count();

		return $item;
	}]);*/

	$get($this, 'gallery_pictures', [
		'uri' => 'gallery_authors/{id:\d+}/gallery_pictures',
		'filter' => $filterBy('author_id'),
		'mutator' => function($item) use ($container) {
			$item['picture'] = $container->gallery->getPictureUrl($item);
			$item['thumb'] = $container->gallery->getThumbUrl($item);
			
			unset($item['picture_type']);
			unset($item['thumb_type']);
			
			if ($item['points']) {
				$item['points'] = explode(',', $item['points']);
			}
	
			return $item;
		}
	]);

	$get($this, 'games');

	$get($this, 'menus');/*, [ 'mutator' => function($item) use ($db) {
		$item['items_count'] = $db->forTable('menu_items')
			->where('section_id', $item['id'])
			->count();

		return $item;
	}]);*/

	//$get($this, 'menus', [ 'uri' => 'games/{id:\d+}/menus', 'filter' => $filterBy('game_id') ]);
	$get($this, 'menu_items', [ 'uri' => 'menus/{id:\d+}/menu_items', 'filter' => $filterBy('section_id') ]);
	$get($this, 'roles');
	$get($this, 'streams');
	$get($this, 'stream_types');
	$get($this, 'users');

	$crud($this, 'articles', [ 'before_save' => function($container, $data, $id) {
		$data['cache'] = null;
		$data['contents_cache'] = null;
		
		return $data;
	} ]);
	
	$crud($this, 'gallery_authors');

	$crud($this, 'gallery_pictures', [ 'after_save' => function($container, $item, $data) {
		$container->gallery->save($item, $data);
	}, 'after_delete' => function($container, $item) {
		$container->gallery->delete($item);
	} ]);
	
	$crud($this, 'games');
	$crud($this, 'menus');
	$crud($this, 'menu_items');
	$crud($this, 'streams');
	$crud($this, 'users');
})->add(new TokenAuthMiddleware($container));

$app->group('/api/v1', function() use ($settings) {
	$this->get('/captcha', function($request, $response, $args) use ($settings) {
		$captcha = $this->captcha->generate($settings['captcha_digits'], true);
		return $this->db->json($response, [ 'captcha' => $captcha['captcha'] ]);
	});
});

$app->get('/admin', function($request, $response, $args) {
	$render = $this->view->render('index.twig');
	$response->write($render);
})->setName('admin.index');

$app->group('/admin', function() use ($access, $settings) {
	$initParams = function($alias) use ($settings) {
		$params = $settings['entities'][$alias];
		$params['tables'] = $settings['tables'];
		
		return $params;
	};
	
	$get = function($app, $alias, $options = []) use ($access, $initParams) {
		$uri = isset($options['uri'])
			? $options['uri']
			: $alias;

		$params = $initParams($alias);

		$app->get('/' . $uri, function($request, $response, $args) use ($params, $options) {
			$templateName = isset($options['template'])
				? ('entities/' . $options['template'])
				: 'entity';

			if (isset($options['mutator'])) {
				$params = $options['mutator']($this, $params, $args);
			}

			return $this->view->render($templateName . '.twig', $params);
		})->add($access($alias, 'read_own', 'admin.index'))->setName('admin.' . $alias);
	};
	
	$get($this, 'games');
	$get($this, 'users');
	$get($this, 'menus');
	$get($this, 'streams');
	$get($this, 'articles', [ 'template' => 'article' ]);
	$get($this, 'gallery_authors', [ 'uri' => 'gallery' ]);

	$get($this, 'menu_items', [
		'uri' => 'menus/{id:\d+}/menu_items',
		'mutator' => function($app, $params, $args) {
		$menuId = $args['id'];
		$menu = $app->db->getEntityById('menus', $menuId);
		$game = $app->db->getEntityById('games', $menu['game_id']);
		
		$params['source'] = "menus/{$menuId}/menu_items";
		$params['breadcrumbs'] = [
			[ 'text' => 'Меню', 'link' => $app->router->pathFor('admin.menus') ],
			[ 'text' => $game ? $game['name'] : '(нет игры)' ],
			[ 'text' => $menu['text'] ],
			[ 'text' => 'Элементы меню' ],
		];
		$params['hidden'] = [
			'section_id' => $menuId,
		];
		
		return $params;
	}]);

	$get($this, 'gallery_pictures', [
		'uri' => 'gallery/{id:\d+}/gallery_pictures',
		'mutator' => function($app, $params, $args) {
		$authorId = $args['id'];
		$author = $app->db->getEntityById('gallery_authors', $authorId);

		$params['source'] = "gallery_authors/{$authorId}/gallery_pictures";
		$params['breadcrumbs'] = [
			[ 'text' => 'Галерея', 'link' => $app->router->pathFor('admin.gallery_authors') ],
			[ 'text' => $author['name'] ],
			[ 'text' => 'Картинки' ],
		];
		$params['hidden'] = [
			'author_id' => $authorId,
		];
		
		return $params;
	}]);
})->add(new AuthMiddleware($container));

$app->group('/auth', function() {
	$this->post('/signup', 'AuthController:postSignUp')->setName('auth.signup');
	$this->post('/signin', 'AuthController:postSignIn')->setName('auth.signin');
})->add(new GuestMiddleware($container));
	
$app->group('/auth', function() {
	$this->get('/signout', 'AuthController:getSignOut')->setName('auth.signout');
	$this->post('/password/change', 'PasswordController:postChangePassword')->setName('auth.password.change');
})->add(new AuthMiddleware($container));
