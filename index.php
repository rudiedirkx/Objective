<?php

define('_START', microtime(1));

require __DIR__ . '/env.php';
require __DIR__ . '/ObjectStore.php';

$store = isset($_REQUEST['store']) ? preg_replace('#[^\w@\.\-]#i', '', basename($_REQUEST['store'])) : '';
define('OBJECT_STORE_FILE', WHERE_STORES_AT . '/' . ( $store ?: 'default$' ) . '.json');

if ( isset($_GET['debug']) ) {
	$store = new ObjectStore(OBJECT_STORE_FILE);

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
	$store = new ObjectStore(OBJECT_STORE_FILE);
	$value = $store->get($get, $exists);

	return $store->output(array('error' => 0, 'exists' => $exists, 'value' => $value));
}

// DELETE
else if ( $delete ) {
	$store = new ObjectStore(OBJECT_STORE_FILE);
	$existed = $store->delete($delete, !empty($_GET['clean']));
	if ( $existed ) {
		$store->save();
	}

	return $store->output(array('error' => 0, 'existed' => $existed));
}

// PUT
else if ( $put && $value !== null && $value !== '' ) {
	$store = new ObjectStore(OBJECT_STORE_FILE);

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
