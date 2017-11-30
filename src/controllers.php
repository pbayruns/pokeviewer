<?php

use Symfony\ Component\ HttpFoundation\ Request;
use Symfony\ Component\ HttpFoundation\ Response;
use Symfony\ Component\ HttpFoundation\ JsonResponse;
use Symfony\ Component\ HttpFoundation\ RedirectResponse;
use Symfony\ Component\ HttpKernel\ Exception\ NotFoundHttpException;

include 'util.php';
//Request::setTrustedProxies(array('127.0.0.1'));

$app->get( '/', function ()use( $app ) {
	return $app[ 'twig' ]->render( 'index.html', array( 'pokedex' => array(
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		),
		array(
			'name' => 'Bulbasaur',
			'number' => 0,
			'url' => 'https://img.pokemondb.net/sprites/x-y/normal/bulbasaur.png'
		)
	) ) );
} )->bind( 'homepage' );

$app->get( '/pokemon/id/{id}', function (Silex\Application $app, $id) use( $app ) {

	$POKEMON_DATA_FILE_NAME = "Pokemon-" . $id . ".txt";
	$API_URL = "https://pokeapi.co/api/v2/pokemon/" . $id;
	$CACHE_LENGTH_HOURS = 48;
	$pokemonData = get_content($POKEMON_DATA_FILE_NAME,$API_URL,$CACHE_LENGTH_HOURS,$fn = '',$fn_args = '');
	if (!isset($pokemonData)) {
        $app->abort(404, "Pokemon $id does not exist.");
    }
	return $app[ 'twig' ]->render( 'pokemondisplay.html', $pokemonData); } 
)->bind( 'display' );

$app->error( function ( \Exception $e, Request $request, $code )use( $app ) {
	if ( $app[ 'debug' ] ) {
		return;
	}

	// 404.html, or 40x.html, or 4xx.html, or error.html
	$templates = array(
		'errors/' . $code . '.html.twig',
		'errors/' . substr( $code, 0, 2 ) . 'x.html.twig',
		'errors/' . substr( $code, 0, 1 ) . 'xx.html.twig',
		'errors/default.html.twig',
	);

	return new Response( $app[ 'twig' ]->resolveTemplate( $templates )->render( array( 'code' => $code ) ), $code );
} );