<?php

use App\Route\GeneratorResolver;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AccessMiddleware;
use App\Middleware\TokenAuthMiddleware;

$access = function($entity, $action, $redirect = null) use ($container) {
	return new AccessMiddleware($container, $entity, $action, $redirect);
};

$settings = $container->get('settings');

$app->get('/', function($request, $response, $args) {
	//return $response->withRedirect($this->router->pathFor('admin.index'));
})->setName('home');

$generatorResolver = new GeneratorResolver($container);

$app->group('/api/v1', function() use ($settings) {
	$this->get('/captcha', function($request, $response, $args) use ($settings) {
		$captcha = $this->captcha->generate($settings['captcha_digits'], true);
		return $this->db->json($response, [ 'captcha' => $captcha['captcha'] ]);
	});
});

$app->group('/api/v1', function() use ($generatorResolver, $settings, $access) {
	foreach ($settings['tables'] as $alias => $table) {
		if (isset($table['api'])) {
			$gen = $generatorResolver->resolve($alias);
			$gen->generateAPIRoutes($this, $access);
		}
	}
})->add(new TokenAuthMiddleware($container));

$app->get('/admin', function($request, $response, $args) use ($settings) {
	return $this->view->render($response, 'index.twig');
})->setName('admin.index');

$app->group('/admin', function() use ($generatorResolver, $settings, $access) {
	foreach (array_keys($settings['entities']) as $entity) {
		$gen = $generatorResolver->resolve($entity);
		$gen->generateAdminPageRoute($this, $access);
	}
})->add(new AuthMiddleware($container));

$app->group('/auth', function() {
	$this->post('/signup', 'AuthController:postSignUp')->setName('auth.signup');
	$this->post('/signin', 'AuthController:postSignIn')->setName('auth.signin');
})->add(new GuestMiddleware($container));
	
$app->group('/auth', function() {
	$this->get('/signout', 'AuthController:getSignOut')->setName('auth.signout');
	$this->post('/password/change', 'PasswordController:postChangePassword')->setName('auth.password.change');
})->add(new AuthMiddleware($container));
