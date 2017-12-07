<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Symfony\Component\Debug\ErrorHandler;

ErrorHandler::register();
date_default_timezone_set( 'America/Detroit' );


$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../veekun-pokedex.sqlite',
    ),
));
// Register our Twig view path
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__ . '/../resources/twig'
));

$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...
	
    return $twig;
});


return $app;
