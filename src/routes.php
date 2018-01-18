<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AccessMiddleware;
use App\Middleware\TokenAuthMiddleware;

$access = function($entity, $action, $redirect = null) use ($container) {
	return new AccessMiddleware($container, $entity, $action, $redirect);
};

$settings = $container->get('settings');

$base = $settings['folders']['base'];
$root = strlen($base) == 0;

$app->group($base, function() use ($root, $settings, $access, $container) {
	// api
	
	$this->group('/api/v1', function() use ($settings) {
		$this->get('/captcha', function($request, $response, $args) use ($settings) {
			$captcha = $this->captcha->generate($settings['captcha_digits'], true);
			return $this->db->json($response, [ 'captcha' => $captcha['captcha'] ]);
		});
	});
	
	$this->group('/api/v1', function() use ($settings, $access, $container) {
		foreach ($settings['tables'] as $alias => $table) {
			if (isset($table['api'])) {
				$gen = $container->resolver->resolveEntity($alias);
				$gen->generateAPIRoutes($this, $access);
			}
		}
	})->add(new TokenAuthMiddleware($container));
	
	// admin
	
	$this->get('/admin', function($request, $response, $args) {
		return $this->view->render($response, 'admin/index.twig');
	})->setName('admin.index');
	
	$this->group('/admin', function() use ($settings, $access, $container) {
		foreach (array_keys($settings['entities']) as $entity) {
			$gen = $container->resolver->resolveEntity($entity);
			$gen->generateAdminPageRoute($this, $access);
		}
	})->add(new AuthMiddleware($container, 'admin.index'));

	// site
	
	$this->get('/news/{id:\d+}', \App\Controllers\Main\NewsController::class . ':item')->setName('main.news');
	$this->get('/news/archive', \App\Controllers\Main\NewsController::class . ':archiveIndex')->setName('main.news.archive');
	$this->get('/news/archive/{year:\d+}', \App\Controllers\Main\NewsController::class . ':archiveYear')->setName('main.news.archive.year');
	$this->get('/rss', \App\Controllers\Main\NewsController::class . ':rss')->setName('main.rss');
	
	$this->get('/articles/{id}[/{cat}]', \App\Controllers\Main\ArticleController::class . ':item')->setName('main.article');
	
	$this->get('/streams', \App\Controllers\Main\StreamController::class . ':index')->setName('main.streams');
	$this->get('/streams/{alias}', \App\Controllers\Main\StreamController::class . ':item')->setName('main.stream');

	$this->get('/gallery', \App\Controllers\Main\GalleryController::class . ':index')->setName('main.gallery');
	$this->get('/gallery/{alias}', \App\Controllers\Main\GalleryController::class . ':author')->setName('main.gallery.author');
	$this->get('/gallery/{alias}/{id}', \App\Controllers\Main\GalleryController::class . ':picture')->setName('main.gallery.picture');
	
	$this->get('/map', \App\Controllers\Main\MapController::class . ':index')->setName('main.map');
	
	$this->get('/comics', \App\Controllers\Main\ComicController::class . ':index')->setName('main.comics');
	$this->get('/comics/series/{alias}', \App\Controllers\Main\ComicController::class . ':series')->setName('main.comics.series');
	$this->get('/comics/series/{alias}/{number:\d+}', \App\Controllers\Main\ComicController::class . ':issue')->setName('main.comics.issue');
	$this->get('/comics/series/{alias}/{number:\d+}/{page:\d+}', \App\Controllers\Main\ComicController::class . ':issuePage')->setName('main.comics.issue.page');
	$this->get('/comics/{alias}', \App\Controllers\Main\ComicController::class . ':standalone')->setName('main.comics.standalone');
	$this->get('/comics/{alias}/{page:\d+}', \App\Controllers\Main\ComicController::class . ':standalonePage')->setName('main.comics.standalone.page');

	$this->get('/recipes/{id:\d+}', \App\Controllers\Main\RecipeController::class . ':item')->setName('main.recipe');
	$this->get('/recipes[/{skill}]', \App\Controllers\Main\RecipeController::class . ':index')->setName('main.recipes');

	$this->get('/events', \App\Controllers\Main\EventController::class . ':index')->setName('main.events');
	$this->get('/events/{id:\d+}', \App\Controllers\Main\EventController::class . ':item')->setName('main.event');

	$this->get('/tags/{tag}', \App\Controllers\Main\TagController::class . ':item')->setName('main.tag');

	$this->get($root ? '/[{game}]' : '[/{game}]', \App\Controllers\Main\NewsController::class . ':index')->setName('main.index');

	// cron
	
	$this->group('/cron', function() {
		$this->get('/streams/refresh', \App\Controllers\Main\StreamController::class . ':refresh')->setName('main.cron.streams.refresh');
	});

	// auth
	
	$this->group('/auth', function() {
		$this->post('/signup', 'AuthController:postSignUp')->setName('auth.signup');
		$this->post('/signin', 'AuthController:postSignIn')->setName('auth.signin');
	})->add(new GuestMiddleware($container, 'main.index'));
		
	$this->group('/auth', function() {
		$this->post('/signout', 'AuthController:postSignOut')->setName('auth.signout');
		$this->post('/password/change', 'PasswordController:postChangePassword')->setName('auth.password.change');
	})->add(new AuthMiddleware($container, 'main.index'));
});
