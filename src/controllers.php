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

const URL_ALL_LIMIT = 10000000;
function addIDsToResultList(array $resultData){
	foreach($resultData['results'] as &$result){
		$result['id'] = getEndingID($result['url']);
	}
	return $resultData;
}

function getIDPokemonList(array $pokemonList){
	foreach($pokemonList as &$poke){
		$poke = addPokemonID($poke);
	}
	return $pokemonList;
}

function addPokemonID(array $pokemon){
	$pokemon['id'] = getEndingID($pokemon['url']);
	return $pokemon;
}

function getPokemonWithCacheURLS(array $pokemon){
	foreach ($pokemon['sprites'] as $key => $url) {
		if(!empty($url)){
			$pokemon['sprites'][$key] = getImage($url);	
		}
	}
	return $pokemon;
}

function getAbilityPokemonWithID($abilityList){
	
	foreach($abilityList['pokemon'] as &$pokemon){
		$pokemon['pokemon'] = addPokemonID($pokemon['pokemon']);
	}
	return $abilityList;
}

function getEndingID($url){
	if(substr($url, -1) == '/') {
		$url = substr($url, 0, -1);
	}
	$url_array = explode('/',$url);
	return end($url_array);
}

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
$app->get( '/pokemon/{id}', function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/pokemon/" . $id;
	$pokemon = json_decode(getJson($API_URL), true);
	if (!isset($pokemon)) {
        $app->abort(404, "Pokemon $id does not exist.");
    }
	$pokemon = getPokemonWithCacheURLS($pokemon);
	return $app[ 'twig' ]->render( 'pokemondisplay.html', $pokemon); } 
)->bind( 'display' );

//list of all pokemon
$app->get( '/pokemon', function () use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/pokemon/?limit=" . URL_ALL_LIMIT;
	$pokemonList = json_decode(getJson($API_URL), true);
	$pokemonList['results'] = getIDPokemonList($pokemonList['results']);
	return $app[ 'twig' ]->render( 'index.html', $pokemonList );
} )->bind( 'pokemonlist' );

$app->get( '/ability/{id}', function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/ability/" . $id;
	$abilityData = json_decode(getJson($API_URL), true);
	if (!isset($abilityData)) {
        $app->abort(404, "Ability $id does not exist.");
	}
	$abilityData = getAbilityPokemonWithID($abilityData);	
	return $app[ 'twig' ]->render( SPECIFIC_ABILITY . EXT, $abilityData); } 
)->bind( SPECIFIC_ABILITY );

$app->get( '/move/{id}', function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/move/" . $id;
	$moveData = json_decode(getJson($API_URL), true);
	return $app[ 'twig' ]->render( 'move.html', $moveData); } 
)->bind( 'move' );

$app->get( '/moves', function () use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/move/?limit=" . URL_ALL_LIMIT;
	$moveData = json_decode(getJson($API_URL), true);

	$moveData = addIDsToResultList($moveData);
	return $app[ 'twig' ]->render( 'movelist.html', $moveData); } 
)->bind( 'movelist' );

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