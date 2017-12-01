<?php 
function getJson($url) {
    // cache files are created like cache/abcdef123456...
    $cacheFile = '.' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . md5($url);

    if (file_exists($cacheFile)) {
        $fh = fopen($cacheFile, 'r');
        $cacheTime = trim(fgets($fh));

        // if data was cached recently, return cached data
        if ($cacheTime > strtotime('-3 days')) {
            return fread($fh, filesize($cacheFile));
        }

        // else delete cache file
        fclose($fh);
        unlink($cacheFile);
    }

    $client = new GuzzleHttp\Client();
	$response =  $client->request('GET', $url, ['verify' => false]);
	$json = $response->getBody();
	
    $fh = fopen($cacheFile, 'w');
    fwrite($fh, time() . "\n");
    fwrite($fh, $json);
    fclose($fh);

    return $json;
}

function getImage($url){

// Time to cache the files (here: 1 week)
define('time_to_cache', 604800);

// Create a local file representation
$local = '.' . DIRECTORY_SEPARATOR . 
		'cache' . DIRECTORY_SEPARATOR .  
		'images' . DIRECTORY_SEPARATOR .
		md5($url);

// Determine whether the local file is too old
if (@filemtime($local) + time_to_cache < time()) {
    // Download a fresh copy
    copy ($url, $local);

    // Store headers in case we need them (see alternative below)
    file_put_contents($local . '.hdr', join($http_response_header, "\n"));
}

// Solution 1: Redirect to the local cache file
header('Location: ' . urlencode($local));
exit();

// Alternative: Send headers and the actual file
// Note that this might cause problems, e.g. due
// to cache fields and the like.

// Read and send headers
foreach(file($local . '.hdr') as $line)
    header($line);

// Read and send the actual file
readfile($local);
}
?>