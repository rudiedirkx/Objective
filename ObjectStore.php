<?php

class ObjectStore {
	public $file;
	public $decodeFail = array();

	function __construct( $file = null, $load = true ) {
		$file or defined('OBJECT_STORE_FILE') and $file = OBJECT_STORE_FILE;
		$this->file = $file;

		$load and $this->load();
	}

	function decode( $data ) {
		$value = @json_decode($data, true);
		if (json_last_error()) {
			$value = $this->decodeFail;
		}
		return $value;
	}

	function encode( $data ) {
		return @json_encode($data);
	}

	function load() {
		return $this->store = $this->decode(@file_get_contents($this->file));
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
		$antiHijack = 'while(1);';
		header('Content-type: text/json');
		header('X-script-time: ' . ((microtime(1) - _START)*1000) . ' ms');
		header('X-anti-hijack: ' . strlen($antiHijack));
		echo $antiHijack . json_encode($data);
		exit;
	}
}
