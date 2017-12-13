<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AccessMiddleware;
use App\Middleware\TokenAuthMiddleware;

$access = function($entity, $action, $redirect = null) use ($container) {
	return new AccessMiddleware($container, $entity, $action, $redirect);
};

$settings = $container->get('settings');

/*$app->get('/', function($request, $response, $args) {
	//return $response->withRedirect($this->router->pathFor('admin.index'));
})->setName('home');*/

$app->group('/api/v1', function() use ($settings) {
	$this->get('/captcha', function($request, $response, $args) use ($settings) {
		$captcha = $this->captcha->generate($settings['captcha_digits'], true);
		return $this->db->json($response, [ 'captcha' => $captcha['captcha'] ]);
	});
});

$app->group('/api/v1', function() use ($settings, $access, $container) {
	foreach ($settings['tables'] as $alias => $table) {
		if (isset($table['api'])) {
			$gen = $container->resolver->resolveEntity($alias);
			$gen->generateAPIRoutes($this, $access);
		}
	}
})->add(new TokenAuthMiddleware($container));

$app->get('/admin', function($request, $response, $args) {
	return $this->view->render($response, 'admin/index.twig');
})->setName('admin.index');

$app->group('/admin', function() use ($settings, $access, $container) {
	foreach (array_keys($settings['entities']) as $entity) {
		$gen = $container->resolver->resolveEntity($entity);
		$gen->generateAdminPageRoute($this, $access);
	}
})->add(new AuthMiddleware($container, 'admin.index'));

$app->group('/main', function() {
	$this->get('/news/{id:\d+}', \App\Controllers\Main\NewsController::class . ':item')->setName('main.news');
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

	$this->get('[/{game}]', \App\Controllers\Main\NewsController::class . ':index')->setName('main.index');
});

$app->group('/cron', function() {
	$this->get('/streams/refresh', \App\Controllers\Main\StreamController::class . ':refresh')->setName('main.cron.streams.refresh');
});

$app->group('/auth', function() {
	$this->post('/signup', 'AuthController:postSignUp')->setName('auth.signup');
	$this->post('/signin', 'AuthController:postSignIn')->setName('auth.signin');
})->add(new GuestMiddleware($container, 'main.index'));
	
$app->group('/auth', function() {
	$this->post('/signout', 'AuthController:postSignOut')->setName('auth.signout');
	$this->post('/password/change', 'PasswordController:postChangePassword')->setName('auth.password.change');
})->add(new AuthMiddleware($container, 'main.index'));
