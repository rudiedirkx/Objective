<?php

define('_START', microtime(1));

require __DIR__ . '/env.php';
require __DIR__ . '/ObjectStore.php';

if ( !ObjectStore::validStoreName(@$_REQUEST['store']) ) {
	$store = new ObjectStore('/tmp/tmp');
	return $store->output(array(
		'error' => 'Invalid store name',
	));
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
$push = @$_REQUEST['push'];
$pull = @$_REQUEST['pull'];
$value = @$_REQUEST['value'];

// GET
if ( $get ) {
	$store = new ObjectStore($store);
	$value = $store->get($get, $exists);

	return $store->output(array(
		'exists' => $exists,
		'value' => $value,
		'untouched' => true,
	));
}

// DELETE
else if ( $delete ) {
	$store = new ObjectStore($store);

	$existed = $store->delete($delete, !empty($_REQUEST['clean']));
	if ( $existed ) {
		$store->save();
	}

	return $store->output(array(
		'existed' => $existed,
		'untouched' => !$existed,
	));
}

// PUT
else if ( $put && strlen($value) ) {
	$store = new ObjectStore($store);

	try {
		$value = $store->decode($value);
	}
	catch ( InvalidArgumentException $ex ) {
		return $store->output(array(
			'error' => 'decode: ' . $ex->getMessage(),
		));
	}

	$store->put($put, $value);
	$store->save();

	return $store->output(array(
		'value' => $value,
		'untouched' => false,
	));
}

// PUSH & PULL
else if ( ($push XOR $pull) && strlen($value) ) {
	$store = new ObjectStore($store);

	$var = $push ?: $pull;
	$unique = !empty($_REQUEST['unique']);

	try {
		$value = $store->decode($value);
	}
	catch ( InvalidArgumentException $ex ) {
		return $store->output(array(
			'error' => 'decode: ' . $ex->getMessage(),
		));
	}

	$list = $store->get($var, $exists);
	if ( !$exists || !is_array($list) ) {
		$list = array();
		$pre = false;
	}
	else {
		$pre = $list;
		$list = array_values($list);
	}

	$index = array_search($value, $list, true);

	function array_unique_nonscalar($array) {
		$out = array();
		foreach ( $array as $value ) {
			if ( !in_array($value, $out) ) {
				$out[] = $value;
			}
		}
		return $out;
	}

	// PUSH
	if ( $push ) {
		$list[] = $value;

		if ( $unique ) {
			$list = array_unique_nonscalar($list);
		}
	}

	// PULL
	else {
		if ( $unique ) {
			$list = array_unique_nonscalar($list);
		}

		if ( $index !== false ) {
			$index = array_search($value, $list, true);
			array_splice($list, $index, 1);
		}
	}

	// Only save if we changed something
	$untouched = true;
	if ( $pre !== $list ) {
		$untouched = false;

		$store->put($var, $list);
		$store->save();
	}

	return $store->output(array(
		'value' => $list,
		'untouched' => $untouched,
	));
}

$store = new ObjectStore('/tmp/tmp');
return $store->output(array(
	'error' => 'Invalid request (action/method/params)',
));
