<?php

use Symfony\ Component\ HttpFoundation\ Request;
use Symfony\ Component\ HttpFoundation\ Response;
use Symfony\ Component\ HttpFoundation\ JsonResponse;
use Symfony\ Component\ HttpFoundation\ RedirectResponse;
use Symfony\ Component\ HttpKernel\ Exception\ NotFoundHttpException;

include 'util.php';
//Request::setTrustedProxies(array('127.0.0.1'));

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
	$API_URL = "http://pokeapi.co/api/v2/pokemon/?limit=10000000";
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
	return $app[ 'twig' ]->render( 'ability.html', $abilityData); } 
)->bind( 'ability' );

$app->get( '/move/{id}', function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/move/" . $id;
	$moveData = json_decode(getJson($API_URL), true);
	if (!isset($moveData)) {
        $app->abort(404, "Move $id does not exist.");
    }
	return $app[ 'twig' ]->render( 'move.html', $moveData); } 
)->bind( 'move' );

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