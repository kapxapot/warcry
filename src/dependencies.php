<?php

use Respect\Validation\Validator as v;

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

$container['comics'] = function($c) {
	return new \App\Gallery\Comics($c);
};

$container['resolver'] = function($c) {
	return new \App\Generators\Resolver($c);
};

$container['cases'] = function($c) {
	return new \Warcry\Util\Cases;
};

$container['view'] = function($c) {
    $settings = $c->get('settings');
    $tws = $settings['view'];

	$templatesPath = __DIR__ . $tws['templates_path'];
	$cachePath = $tws['cache_path'];
	if ($cachePath) {
		$cachePath = __DIR__ . $cachePath;
	}

	$view = new \Slim\Views\Twig($templatesPath, [
		'cache' => $cachePath
	]);

	$view->addExtension(new \Slim\Views\TwigExtension($c->router, $c->request->getUri()));
	$view->addExtension(new \App\Twig\Extensions\AccessRightsExtension($c));
	
	// set globals
    $globals = $settings['view_globals'];
	foreach ($globals as $key => $value) {
		$view[$key] = $value;
	}

	$view['auth'] = [
		'check' => $c->auth->check(),
		'user' => $c->auth->getUser(),
		'role' => $c->auth->getRole(),
		'editor' => $c->auth->isEditor(),
	];
	
	$view['image_types'] = $c->gallery->buildTypesString();
	
	$view['tables'] = $settings['tables'];
	$view['entities'] = $settings['entities'];
	
	$view['api'] = $settings['folders']['api'];

    return $view;
};

$container['cache'] = function($c) {
	return new \Warcry\Cache($c);
};

$container['session'] = function($c) {
    $settings = $c->get('settings');
    $base = $settings['folders']['base'];
    
	$name = 'sessionContainer' . $base;
	
	return new \Warcry\Session($name);
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

$dbs = $container->get('settings')['db'];

$container['db'] = function($c) use ($dbs) {
	\ORM::configure("mysql:host={$dbs['host']};dbname={$dbs['database']}");
	\ORM::configure("username", $dbs['user']);
	\ORM::configure("password", $dbs['password']);
	\ORM::configure("driver_options", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	
	return new \App\DB\DbLayer($c);
};

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
	'driver' => 'mysql',
	'host' => $dbs['host'],
	'database' => $dbs['database'],
	'username' => $dbs['user'],
	'password' => $dbs['password'],
	'charset' => 'utf8',
	'collation' => 'utf8_general_ci',
	'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['eloquent'] = function($c) use ($capsule) {
	return $capsule;
};

// legacy

$container['legacyDecorator'] = function($c) {
	return new \App\Legacy\Decorator($c);
};

$container['builder'] = function($c) {
	return new \App\Legacy\Builder($c);
};

$container['legacyRouter'] = function($c) {
	return new \App\Legacy\Router($c);
};

$container['legacyArticleParser'] = function($c) {
	return new \App\Legacy\Parsing\ArticleParser($c);
};

$container['legacyNewsParser'] = function($c) {
	return new \App\Legacy\Parsing\NewsParser($c);
};

$container['legacyForumParser'] = function($c) {
	return new \App\Legacy\Parsing\ForumParser($c);
};

// handlers

$container['notFoundHandler'] = function($c) {
	return new \App\Handlers\NotFoundHandler($c);
};

if ($debug !== true) {
	$container['errorHandler'] = function($c) {
		return new \App\Handlers\ErrorHandler($c);
	};
}

$container['notAllowedHandler'] = function($c) {
	return new \App\Handlers\NotAllowedHandler($c);
};

// external

$container['twitch'] = function($c) {
	return new \App\External\Twitch($c);
};

$container['telegram'] = function($c) {
	return new \App\External\Telegram($c);
};
