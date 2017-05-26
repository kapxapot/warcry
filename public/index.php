<?php

if (isset($_GET['debug']) or true) {
	error_reporting(E_ALL & ~E_NOTICE);
	ini_set("display_errors", 1);
}

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__.$url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__.'/../vendor/autoload.php';

$lib = __DIR__.'/../../../../lib/';

require_once $lib.'idiorm/idiorm.php';
require_once $lib.'Twig/Autoloader.php';

Twig_Autoloader::register();

require_once $lib.'twig-extension.php';

session_start();

$src = __DIR__.'/../src/';

// Instantiate the app
$settings = require $src.'settings.php';
$app = new \Slim\App($settings);
$container = $app->getContainer();

// Set up dependencies
require $src.'dependencies.php';

// Register middleware
require $src.'middleware.php';

// Register routes
require $src.'routes.php';

// Run app
$app->run();
