<?php

use Symfony\ Component\ HttpFoundation\ Request;
use Symfony\ Component\ HttpFoundation\ Response;
use Symfony\ Component\ HttpFoundation\ JsonResponse;
use Symfony\ Component\ HttpFoundation\ RedirectResponse;
use Symfony\ Component\ HttpKernel\ Exception\ NotFoundHttpException;

//util functions for caching
include 'util.php';
const API_ROOT = 'http://pokeapi.co/api/v2/';
const ALL_POKEMON = 'pokemonlist';
const SPECIFIC_POKEMON = 'pokemondisplay';
const ALL_ABILITIES = 'abilitylist';
const SPECIFIC_ABILITY = 'abilitydisplay';
const EXT = '.HTML';
const URLS = array(
	SPECIFIC_POKEMON => '/pokemon/{id}',
	ALL_POKEMON => '/pokemon',
	SPECIFIC_ABILITY => '/abilities/{id}',
	ALL_ABILITIES => '/abilities'
);
//Request::setTrustedProxies(array('127.0.0.1'));

//specific pokemon endpoint
$app->get( URLS[SPECIFIC_POKEMON], function (Silex\Application $app, $id) use ( $app ) {

	$API_URL = API_ROOT . "pokemon/" . $id;
	$pokemonData = json_decode(getJson($API_URL), true);
	if (!isset($pokemonData)) {
        $app->abort(404, "Pokemon $id does not exist.");
    }
	foreach ($pokemonData['sprites'] as $key => $url) {
		if(!empty($url)){
			$pokemonData['sprites'][$key] = getImage($url);	
		}
	}
	foreach ($pokemonData['abilities'] as &$ability) {
		$ability['ability']['id'] = basename($ability['ability']['url']);
	}
	return $app[ 'twig' ]->render( SPECIFIC_POKEMON.EXT, $pokemonData); } 
)->bind( SPECIFIC_POKEMON );

//list of all pokemon
$app->get( URLS[ALL_POKEMON], function () use ( $app ) {
	$API_URL = API_ROOT . "pokemon/?limit=10000000";
	$pokemonList = json_decode(getJson($API_URL), true);
	foreach($pokemonList['results'] as &$poke){
		$poke['id'] = basename($poke['url']);
	}
	return $app[ 'twig' ]->render(ALL_POKEMON.EXT, $pokemonList );
} )->bind( ALL_POKEMON );

//specific ability endpoint
$app->get( URLS[SPECIFIC_ABILITY], function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = API_ROOT . "ability/" . $id;
	$abilityData = json_decode(getJson($API_URL), true);
	if (!isset($abilityData)) {
        $app->abort(404, "Ability $id does not exist.");
    }
	return $app[ 'twig' ]->render( SPECIFIC_ABILITY . EXT, $abilityData); } 
)->bind( SPECIFIC_ABILITY );

//errors
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