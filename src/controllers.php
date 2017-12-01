<?php

use Symfony\ Component\ HttpFoundation\ Request;
use Symfony\ Component\ HttpFoundation\ Response;
use Symfony\ Component\ HttpFoundation\ JsonResponse;
use Symfony\ Component\ HttpFoundation\ RedirectResponse;
use Symfony\ Component\ HttpKernel\ Exception\ NotFoundHttpException;

include 'util.php';
//Request::setTrustedProxies(array('127.0.0.1'));

//specific pokemon endpoint
$app->get( '/pokemon/id/{id}', function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/pokemon/" . $id;
	$pokemonData = json_decode(getJson($API_URL), true);
	if (!isset($pokemonData)) {
        $app->abort(404, "Pokemon $id does not exist.");
    }
	foreach ($pokemonData['sprites'] as $key => $url) {
		if(!empty($url)){
			$pokemonData['sprites'][$key] = getImage($url);	
		}
	}
	return $app[ 'twig' ]->render( 'pokemondisplay.html', $pokemonData); } 
)->bind( 'display' );

//list of all pokemon
$app->get( '/pokemon', function () use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/pokemon/?limit=10000000";
	$pokemonList = json_decode(getJson($API_URL), true);
	//add the pokemon ids to the results
	//echo var_dump($pokemonList['results']);
	foreach($pokemonList['results'] as &$poke){
		$poke['id'] = basename($poke['url']);
	}
	return $app[ 'twig' ]->render( 'index.html', $pokemonList );
} )->bind( 'pokemonlist' );


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