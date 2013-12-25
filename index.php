<?php

define('_START', microtime(1));

require __DIR__ . '/env.php';
require __DIR__ . '/ObjectStore.php';

$store = isset($_REQUEST['store']) ? preg_replace('#[^\w@\.\-]#i', '', basename($_REQUEST['store'])) : '';
define('OBJECT_STORE_FILE', WHERE_STORES_AT . '/' . ( $store ?: 'default$' ) . '.json');
// exit(OBJECT_STORE_FILE);

if ( isset($_GET['debug']) ) {
	header('Content-type: text/plain');
	$store = new ObjectStore;
	// Not CORS, not JSON
	print_r($store->store);
	exit;
}

$get = @$_REQUEST['get'];
$put = @$_REQUEST['put'];
$value = @$_REQUEST['value'];

if ( $get ) {
	$store = new ObjectStore;
	$value = $store->get($get, $exists);

	return $store->output(array('error' => 0, 'exists' => $exists, 'value' => $value));
}

else if ( $put && $value !== null && $value !== '' ) {
	$store = new ObjectStore;

	$value = $store->decode($value);
	$store->put($put, $value);
	$store->save();

	return $store->output(array('error' => 0, 'value' => $value));
}
