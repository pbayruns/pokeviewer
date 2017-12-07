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
const SPECIFIC_POKEMON = 'pokemon';
const ALL_ABILITIES = 'abilitylist';
const SPECIFIC_ABILITY = 'ability';
const ALL_MOVES = 'movelist';
const SPECIFIC_MOVE = 'move';
const EXT = '.HTML';
const URLEND = '/';
const URLS = array(
	SPECIFIC_POKEMON => '/pokemon/{id}'. URLEND,
	ALL_POKEMON => '/pokemon'. URLEND,
	SPECIFIC_ABILITY => '/abilities/{id}'. URLEND,
	ALL_ABILITIES => '/abilities'. URLEND,
	SPECIFIC_MOVE => '/move/{id}'. URLEND,
	ALL_MOVES => '/moves'. URLEND
);
const URL_ALL_LIMIT = 10000000;
//Request::setTrustedProxies(array('127.0.0.1'));

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

function addPokemonID($pokemon){
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

function getPokemonDetailList($pokemonList){
	foreach($pokemonList['results'] as &$pokemon){
		
		$json = getJson($pokemon['url']);
		//echo $json;
			$pokemon = json_decode($json, true);			
		
		//$pokemon = (array) array_merge((array) $pokemon, (array) json_decode(getJson($pokemon['url']), true));		
		
		$pokemon['sprites']['front_default'] = getImage($pokemon['sprites']['front_default']);
	}
	return $pokemonList;
}

function getDetailList($apiList){
	foreach($apiList['results'] as &$result){
		$result = json_decode(getJson($result['url']), true);
		//$result['sprites']['front_default'] = getImage($result['sprites']['front_default']);
	}
	return $apiList;
}

function addTypesToJSON(&$json){
	$json['types'] = json_decode(getJson(API_ROOT . 'type/'), true);
	return $json;
}

function getArrayFromCSV($csv){
	return explode(',', $csv);
}

function stringsToArrays(&$data, $target_key){
	foreach($data as &$data_entry){
		foreach($data_entry as $key => &$value){
			if($key == $target_key){
				$value = explode(',', $value);
				foreach($value as &$value_entry){
					$array = explode('_', $value_entry);
					$value_entry = array(
						'id' => $array[0],
						'value' => $array[1]
					);
				}
			}
		}
	}
	return $data;
}

function idValStringsToArray($data, $target_key){
		foreach($data as $key => &$value){
			if($key == $target_key){
				$value = explode(',', $value);
				foreach($value as &$value_entry){
					$array = explode('_', $value_entry);
					$value_entry = array(
						'id' => $array[0],
						'value' => $array[1]
					);
				}
			}
		}
	return $data;
}

function formatJSON($json_string){
	$badchar=array(
		// control characters
		chr(0), chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), chr(9), chr(10),
		chr(11), chr(12), chr(13), chr(14), chr(15), chr(16), chr(17), chr(18), chr(19), chr(20),
		chr(21), chr(22), chr(23), chr(24), chr(25), chr(26), chr(27), chr(28), chr(29), chr(30),
		chr(31),
		// non-printing characters
		chr(127)
	);
	//replace the unwanted chars
	$json_string = str_replace($badchar, '', $json_string);
	$json_string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json_string);
	$json_string = str_replace("'", '"', $json_string);
	$json_string =str_replace('&quot;', '"', $json_string);
	return $json_string;
}
//specific pokemon endpoint
function getJSONString($fields){
	$string = "'{' || ";
	$end = " || ',' || ";
	foreach($fields as $key => $value){
		$string .= "' ''".$key."'': '|| ";
		if($value['is_string']){
			$string .= " '''' || " .$value['value']. " || '''' ";
		}else{
			$string .= $value['value'];
		}
		$string .= " || ',' || ";
	}
	$string = substr($string, 0, -1*strlen($end)) . " || '}'";
	return $string;
}

function getQueryJSONArray($name, $fields){
	return getQueryJSONArrayAs($name, $fields, true);
}
function getQueryJSONArrayAs($name, $fields, $withAS){
	$fields = getJSONString($fields);
	$query = "'{ ''".$name."'': [' || GROUP_CONCAT(DISTINCT ".$fields.") || '] }'";
	if($withAS){
		$query .= " AS " .$name;
	}
	return $query;
}

function getUpdatedJSON($array, $accessor){
	$string = formatJSON($array[$accessor]);
	$decoded = json_decode($string, true);
	return $decoded[$accessor];
}

$app->get( URLS[SPECIFIC_POKEMON], function (Silex\Application $app, $id) use ( $app ) {

	$abilityFields = array(
		'id' => array(
			'is_string' => false,
			'value' => 'a.id' 
		),
		'identifier' => array(
			'is_string' => true,
			'value' => 'a.identifier' 
		),
		'slot' => array(
			'is_string' => false,
			'value' => 'pa.slot' 
		),
		'is_hidden' => array(
			'is_string' => false,
			'value' => 'pa.is_hidden' 
		)
	);

	$typeFields = array(
		'id' => array(
			'is_string' => false,
			'value' => 't.id' 
		),
		'identifier' => array(
			'is_string' => true,
			'value' => 't.identifier' 
		)
	);

	$statFields = array(
		'id' => array(
			'is_string' => false,
			'value' => 's.id' 
		),
		'identifier' => array(
			'is_string' => true,
			'value' => 's.identifier' 
		),
		'base_stat' => array(
			'is_string' => false,
			'value' => 'ps.base_stat' 
		),
		'effort' => array(
			'is_string' => false,
			'value' => 'ps.effort' 
		)
	);

	$moveFields = array(
		'id' => array(
			'is_string' => false,
			'value' => 'm.id' 
		),
		'identifier' => array(
			'is_string' => true,
			'value' => 'm.identifier' 
		),
		'type' => array(
			'is_string' => true,
			'value' => 'mtypes.identifier' 
		),
		'power' => array(
			'is_string' => false,
			'value' => 'm.power' 
		),
		'accuracy' => array(
			'is_string' => false,
			'value' => 'm.accuracy' 
		),
		'pp' => array(
			'is_string' => false,
			'value' => 'm.pp' 
		),
		'priority' => array(
			'is_string' => false,
			'value' => 'm.priority' 
		),
		'target' => array(
			'is_string' => true,
			'value' => 'mt.identifier' 
		),'level' => array(
			'is_string' => false,
			'value' => 'pm.level' 
		),'learn_method' => array(
			'is_string' => true,
			'value' => 'pmm.identifier'
		)
	);

	$STATS = 'stats';
	$ABILITIES = 'abilities';
	$TYPES = 'types';
	$MOVES = 'moves';
	$abilitySQL = getQueryJSONArray($ABILITIES, $abilityFields);
	$typesSQL = getQueryJSONArray($TYPES, $typeFields);
	$statSQL = getQueryJSONArray($STATS, $statFields);
	$moveSQL = getQueryJSONArray($MOVES, $moveFields);
	
	$sql = "SELECT p.*, "
		.$typesSQL.	 	" , "
		.$abilitySQL.	" , "
		.$statSQL.		" , "
		.$moveSQL.
	" FROM pokemon p
	JOIN pokemon_types pt ON (p.id = pt.pokemon_id)
	JOIN types t ON (pt.type_id = t.id)
	JOIN pokemon_abilities pa ON (pa.pokemon_id = p.id)
	JOIN abilities a ON (pa.ability_id = a.id) 
	JOIN pokemon_stats ps ON (ps.pokemon_id = p.id)
	JOIN stats s ON (s.id = ps.stat_id)
	JOIN pokemon_moves pm ON (p.id = pm.pokemon_id)
	JOIN moves m ON (m.id = pm.move_id)
	JOIN pokemon_move_methods pmm ON (pmm.id = pm.pokemon_move_method_id)
	JOIN move_targets mt ON (m.target_id = mt.id)
	JOIN types mtypes ON (mtypes.id = m.type_id)
	WHERE p.id = :id
	GROUP BY p.id";
	$stmt = $app['db']->prepare($sql);	
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$pokemon = $stmt->fetchAll();
	if(isset($pokemon) && count($pokemon) == 1){
		$pokemon = $pokemon[0];
	}else{
		$app->abort(404, "Pokemon $id does not exist.");
	}
	//$pokemon[$ABILITIES] = getUpdatedJSON($pokemon, $ABILITIES);
	$string = formatJSON($pokemon[$ABILITIES]);
	$decoded = json_decode($string, true);
	$pokemon[$ABILITIES] = getUpdatedJSON($pokemon, $ABILITIES);//$decoded[$ABILITIES];	
	$pokemon[$TYPES] = getUpdatedJSON($pokemon, $TYPES);
	$pokemon[$STATS] = getUpdatedJSON($pokemon, $STATS);
	$pokemon[$MOVES] = getUpdatedJSON($pokemon, $MOVES);

	return $app[ 'twig' ]->render( SPECIFIC_POKEMON . EXT, $pokemon); } 

)->bind( SPECIFIC_POKEMON );

//list of all pokemon
$app->get( URLS[ALL_POKEMON], function () use ( $app ) {
	
	$typeFields = array(
		'id' => array(
			'is_string' => false,
			'value' => 't.id' 
		),
		'identifier' => array(
			'is_string' => true,
			'value' => 't.identifier' 
		)
	);
	$typesSQL = getQueryJSONArray('types', $typeFields);
	
	$sql = "SELECT p.*, ".$typesSQL." FROM pokemon p
	JOIN pokemon_types pt ON (p.id = pt.pokemon_id)
	JOIN types t ON (pt.type_id = t.id)
	GROUP BY p.id";

	$pokemon = $app['db']->fetchAll($sql);
	foreach($pokemon as &$poke){
		$poke['types'] = getUpdatedJSON($poke, 'types');	
	}
	
	$sql = "SELECT identifier FROM types";
	$types = $app['db']->fetchAll($sql);
	
	return $app[ 'twig' ]->render( ALL_POKEMON . EXT, array('pokemon' => $pokemon, 'types' => $types) );
} )->bind( ALL_POKEMON );

//specific ability endpoint
$app->get( URLS[SPECIFIC_ABILITY], function (Silex\Application $app, $id) use ( $app ) {
	
	$sql = "SELECT a.*, 
       ap.effect,
	   ap.short_effect,
       t.id AS 'type_id', 
	   p.id AS 'pokemon_id',
	   p.identifier AS 'pokemon_identifier',
       t.identifier AS 'type_identifier',
	   pt.slot AS 'type_slot',
       pokea.id AS 'pokemon_ability_id',
       pokea.identifier AS 'pokemon_ability_identifier',
       pokepa.slot AS 'ability_slot', 
       pokepa.is_hidden 
FROM   abilities a 
       JOIN ability_prose ap 
         ON ( a.id = ap.ability_id ) 
       JOIN pokemon_abilities pa 
         ON ( a.id = pa.ability_id ) 
       JOIN pokemon p 
         ON ( p.id = pa.pokemon_id ) 
       JOIN pokemon_types pt 
         ON ( p.id = pt.pokemon_id ) 
       JOIN types t 
         ON ( t.id = pt.type_id ) 
       JOIN pokemon_abilities pokepa 
         ON ( pokepa.pokemon_id = p.id ) 
       JOIN abilities pokea 
         ON ( pokea.id = pokepa.ability_id ) 
WHERE  a.id = :id
       AND ap.local_language_id = 9; ";
	
	$stmt = $app['db']->prepare($sql);	
	$stmt->bindValue('id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$results = $stmt->fetchAll();
	
	$pokemonData = array();
	$pokemon = array();
	foreach($results as $row){
		
		$pokeID = $row['pokemon_id'];
		$pokeIdentifier = $row['pokemon_identifier'];
		if(!isset($pokemon[$pokeID])){
			$pokemon[$pokeID] = array();
			$pokemon[$pokeID]['id'] = $pokeID;
			$pokemon[$pokeID]['identifier'] = $pokeIdentifier;
		}
		
		if(!isset($pokemonData[$pokeID])){
			$pokemonData[$pokeID] = array();
		}
		array_push($pokemonData[$pokeID], $row);
	}
	foreach($pokemonData as &$pokeRows){
		foreach($pokeRows as &$pokeRow){
			
			$pokeID = $pokeRow['pokemon_id'];
			$currPoke = &$pokemon[$pokeID];
			
			$abilityID = $pokeRow['pokemon_ability_id'];
			$abilityIdentifier = $pokeRow['pokemon_ability_identifier'];
			$abilitySlot = $pokeRow['ability_slot'];
			$abilityIsHidden = $pokeRow['is_hidden'];
			$currAbility = $currPoke['abilities'][$abilityID];
			
			$typeID = $pokeRow['type_id'];
			$typeIdentifier = $pokeRow['type_identifier'];
			$typeSlot = $pokeRow['type_slot'];
			$currType = $currPoke['types'][$typeID];
			
			if(!isset($currPoke['types'])){
				$currPoke['types'] = array();
			}
			if(!isset($currPoke['abilities'])){
				$currPoke['abilities'] = array();
			}
			$currType['id'] = $typeID;
			$currType['identifier'] = $typeIdentifier;
			$currType['slot'] = $typeSlot;
			
			$currAbility['id'] = $abilityID;
			$currAbility['slot'] = $abilitySlot;
			$currAbility['identifier'] = $abilityIdentifier;
			$currAbility['is_hidden'] = $abilityIsHidden;
			
			$currPoke['types'][$typeSlot] = $currType;
			$currPoke['abilities'][$abilitySlot] = $currAbility;
		}
	}
	
	$first = $results[0];
	$abilityData = array(
		'id' => $first['id'],
		'identifier' => $first['identifier'],
		'generation_id' => $first['generation_id'],
		'is_main_series' => $first['is_main_series'],
		'short_effect' => $first['short_effect'],
		'effect' => $first['effect'],
		'pokemon' => $pokemon
	);
	//echo "<pre>"; print_r($abilityData); echo "</pre>";
	
	if(!isset($abilityData)){
		$app->abort(404, "Ability $id does not exist.");
	}
	return $app[ 'twig' ]->render( SPECIFIC_ABILITY . EXT, $abilityData); } 
)->bind( SPECIFIC_ABILITY );


$app->get( URLS[SPECIFIC_MOVE], function (Silex\Application $app, $id) use ( $app ) {
	$API_URL = API_ROOT . "move/" . $id;
	$moveData = json_decode(getJson($API_URL), true);
	return $app[ 'twig' ]->render( SPECIFIC_MOVE.EXT, $moveData); } 
)->bind( SPECIFIC_MOVE );

$app->get( URLS[ALL_MOVES], function () use ( $app ) {
	$API_URL = "http://pokeapi.co/api/v2/move/?limit=" . URL_ALL_LIMIT;
	$moveData = json_decode(getJson($API_URL), true);
	$moveData = addIDsToResultList($moveData);
	return $app[ 'twig' ]->render( ALL_MOVES.EXT, $moveData); } 
)->bind( ALL_MOVES );

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