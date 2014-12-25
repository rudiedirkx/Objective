<?php

class ObjectStore {

	public $file = '';
	public $store = null;

	function __construct( $file, $load = true ) {
		$this->file = $file;
		$load and $this->load();
	}

	function decode( $data ) {
		$value = @json_decode($data, true);
		if ($error = json_last_error()) {
			throw new Exception($error);
		}
		return $value;
	}

	function encode( $data ) {
		return @json_encode($data);
	}

	function load() {
		$encoded = @file_get_contents($this->file);
		return $this->store = $encoded ? $this->decode($encoded) : array();
	}

	function save() {
		return file_put_contents($this->file, $this->encode($this->store));
	}

	function delete( $name, $clean = false ) {
		$parents = explode('.', $name);
		$child = array_pop($parents);

		$container = &$this->store;
		foreach ( $parents as $parent ) {
			// Next child doesn't exist
			if ( !isset($container[$parent]) || !is_array($container[$parent]) ) {
				return false;
			}

			$container = &$container[$parent];
		}

		// Last child doesn't exist
		if ( !array_key_exists($child, $container) ) {
			return false;
		}

		// Remove last child
		unset($container[$child]);

		// Clean up empty container (expensive)
		if ( $clean && $container === array() && $parents ) {
			$name = implode('.', $parents);
			return $this->delete($name, $clean);
		}

		return true;
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
		header('X-anti-hijack: ' . strlen($antiHijack));

		$output = $antiHijack . $this->encode($data);

		header('X-script-time: ' . ((microtime(1) - _START)*1000) . ' ms');
		echo $output;
		exit;
	}
}
