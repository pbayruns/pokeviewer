<?php 

function getJson($url) {
    // cache files are created like cache/abcdef123456...
<<<<<<< HEAD
    $cacheFile = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . md5($url);
=======
    $cacheFile = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .'cache' . DIRECTORY_SEPARATOR . md5($url);
>>>>>>> 04655665c411f08190b13bf758824024fe716a1f
	
    if (file_exists($cacheFile)) {
        $fh = fopen($cacheFile, 'r');
        $cacheTime = trim(fgets($fh));

        // if data was cached recently, return cached data
        if ($cacheTime > strtotime('-7 days')) {
            return fread($fh, filesize($cacheFile));
        }

        // else delete cache file
        fclose($fh);
        unlink($cacheFile);
    }

    $client = new GuzzleHttp\Client();
	$response =  $client->request('GET', $url);
	$json = $response->getBody();
	
    $fh = fopen($cacheFile, 'w');
    fwrite($fh, time() . "\n");
    fwrite($fh, $json);
    fclose($fh);

    return $json;
}

function getImage($url){

// Time to cache the files (here: 1 week)
if (!defined('time_to_cache')) define('time_to_cache', 604800);

// Create a local file representation
<<<<<<< HEAD
$local = $_SERVER['DOCUMENT_ROOT']  . DIRECTORY_SEPARATOR .'cache' . DIRECTORY_SEPARATOR .  
=======
$local = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .'cache' . DIRECTORY_SEPARATOR .  
>>>>>>> 04655665c411f08190b13bf758824024fe716a1f
		'images' . DIRECTORY_SEPARATOR .
		md5($url);

// Determine whether the local file is too old
if (@filemtime($local) + time_to_cache < time()) {
    // Download a fresh copy
    copy ($url, $local);
}

	return $local;
}
?>