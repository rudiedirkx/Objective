<?php

define('_START', microtime(1));
define('OBJECT_STORE_FILE', __DIR__ . '/object-store.json');

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

else if ( $put && $value !== null ) {
	$store = new ObjectStore;

	$value = $store->decode($value);
	$store->put($put, $value);
	$saved = $store->save();

	return $store->output(array('error' => (int)!$saved, 'saved' => $saved, 'value' => $value));
}



class ObjectStore {
	public $file;
	function __construct( $file = null, $load = true ) {
		$file or defined('OBJECT_STORE_FILE') and $file = OBJECT_STORE_FILE;
		$this->file = $file;

		$load and $this->load();
	}

	function decode( $data ) {
		return @json_decode($data, true) ?: array();
	}

	function encode( $data ) {
		return @json_encode($data);
	}

	function load() {
		return $this->store = $this->decode(file_get_contents($this->file));
	}

	function save() {
		return file_put_contents($this->file, $this->encode($this->store));
	}

	function put( $name, $value ) {
		$parents = explode('.', $name);
		$child = array_pop($parents);

		$container = &$this->store;
		foreach ( $parents as $parent ) {
			if ( !isset($container[$parent]) || !is_array($container[$parent]) ) {
				$container[$parent] = array();
			}

			$container = &$container[$parent];
		}

		$container[$child] = $value;
	}

	function get( $name, &$found = null ) {
		$parents = explode('.', $name);
		$child = array_pop($parents);

		$container = $this->store;
		foreach ( $parents as $parent ) {
			if ( !isset($container[$parent]) ) {
				$found = false;
				return;
			}

			$container = $container[$parent];
		}

		if ( $found = array_key_exists($child, $container) ) {
			return $container[$child];
		}
	}

	function output( $data, $cors = true ) {
		$cors && header('Access-Control-Allow-Origin: *');
		header('Content-type: text/json');
		header('X-script-time: ' . ((microtime(1) - _START)/1000) . ' ms');
		echo json_encode($data);
		exit;
	}
}


