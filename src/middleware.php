<?php

$app->add(new \Warcry\Slim\Middleware\SlashMiddleware($container));
$app->add(new \App\Middleware\CookieAuthMiddleware($container));
