<?php

define('_START', microtime(1));

require __DIR__ . '/env.php';
require __DIR__ . '/ObjectStore.php';

if ( !ObjectStore::validStoreName(@$_REQUEST['store']) ) {
	exit('Invalid store name.');
}
$store = WHERE_STORES_AT . '/' . ObjectStore::filename($_REQUEST['store']);

if ( isset($_GET['debug']) ) {
	$store = new ObjectStore($store);

	// Not CORS, not JSON
	header('Content-type: text/plain');
	print_r($store->store);
	exit;
}

$get = @$_REQUEST['get'];
$put = @$_REQUEST['put'];
$delete = @$_REQUEST['delete'];
$value = @$_REQUEST['value'];

// GET
if ( $get ) {
	$store = new ObjectStore($store);
	$value = $store->get($get, $exists);

	return $store->output(array('error' => 0, 'exists' => $exists, 'value' => $value));
}

// DELETE
else if ( $delete ) {
	$store = new ObjectStore($store);
	$existed = $store->delete($delete, !empty($_GET['clean']));
	if ( $existed ) {
		$store->save();
	}

	return $store->output(array('error' => 0, 'existed' => $existed));
}

// PUT
else if ( $put && $value !== null && $value !== '' ) {
	$store = new ObjectStore($store);

	try {
		$value = $store->decode($value);
	}
	catch ( Exception $ex ) {
		return $store->output(array('error' => 'decode: ' . $ex->getMessage()));
	}

	$store->put($put, $value);
	$store->save();

	return $store->output(array('error' => 0, 'value' => $value));
}

exit('Invalid request.');
