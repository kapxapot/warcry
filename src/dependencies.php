<?php

use Respect\Validation\Validator as v;

function dd($a) {
	var_dump($a);
	die();
}

$container['logger'] = function($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new \Monolog\Logger($settings['name']);

    //$logger->pushProcessor(new \Monolog\Processor\UidProcessor());

    $logger->pushProcessor(function($record) use ($c) {
    	$user = $c->auth->getUser();
    	if ($user) {
	    	$record['extra']['user'] = $c->auth->userString();
    	}
	    
	    $token = $c->auth->getToken();
	    if ($token) {
	    	$record['extra']['token'] = $c->auth->tokenString();
	    }
	
	    return $record;
	});

    $logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . $settings['path'], \Monolog\Logger::DEBUG));
    

    return $logger;
};

$container['auth'] = function($c) {
	return new \App\Auth\Auth($c);
};

$container['captcha'] = function($c) {
	return new \App\Auth\Captcha($c);
};

$container['access'] = function($c) {
	return new \App\Auth\Access($c);
};

$container['gallery'] = function($c) {
	return new \App\Gallery\Gallery($c);
};

$container['view'] = function($c) {
    $settings = $c->get('settings');
    $tws = $settings['view'];

	$loader = new \Twig_Loader_Filesystem(__DIR__ . $tws['templates_path']);
	$view = new \Twig_Environment($loader, array(
	    'cache' => $tws['cache_path'],
	));
	
	$view->addExtension(new \Slim\Views\TwigExtension($c->router, $c->request->getUri()));
	$view->addExtension(new \App\Twig\Extensions\AccessRightsExtension($c));
	
	// set globals
    $globals = $settings['view_globals'];
	foreach ($globals as $key => $value) {
		$view->addGlobal($key, $value);
	}

	$view->addGlobal('auth', [
		'check' => $c->auth->check(),
		'user' => $c->auth->getUser(),
		'role' => $c->auth->getRole()
	]);
	
	$view->addGlobal('image_types', $c->gallery->buildTypesString());

    return $view;
};

$container['cache'] = function($c) {
	return new \Warcry\Cache($c);
};

$container['validator'] = function($c) {
	$validator = new \App\Validation\Validator($c);
	
	$dictionaries = $c->get('settings')['localization'];
	\App\Validation\Validator::setDictionaries($dictionaries);
	
	return $validator;
};

v::with('Warcry\\Validation\\Rules\\');
v::with('App\\Validation\\Rules\\');

$container['AuthController'] = function($c) {
	return new \App\Controllers\Auth\AuthController($c);
};

$container['PasswordController'] = function($c) {
	return new \App\Controllers\Auth\PasswordController($c);
};

$container['db'] = function($c) {
	$dbs = $c->get('settings')['db'];
	
	ORM::configure("mysql:host={$dbs['host']};dbname={$dbs['database']}");
	ORM::configure("username", $dbs['user']);
	ORM::configure("password", $dbs['password']);
	ORM::configure("driver_options", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	
	return new \App\DB\DbHelper($c);
};

$container['notFoundHandler'] = function($c) {
	return function($request, $response) use ($c) {
		$render = $c->view->render('errors/404.twig');
		$response->write($render);
		
		return $response->withStatus(404);
	};
};
