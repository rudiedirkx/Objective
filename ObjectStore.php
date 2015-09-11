<?php

class ObjectStore {

	static function validStoreName( $name ) {
		return $name && is_scalar($name) && preg_match('#^[\w\d\-@\.]{6,40}$#', $name);
	}

	static function filename( $store ) {
		return preg_replace('#\.+#', '.', $store) . '.json';
	}

	public $file = '';
	public $store = null;
	public $antiHijack = 'while(1);';

	function __construct( $file, $load = true ) {
		$this->file = $file;
		$load and $this->load();
	}

	function decode( $data ) {
		$value = @json_decode($data, true);
		if ($error = json_last_error()) {
			throw new InvalidArgumentException('json decode error # ' . $error);
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
		if ( $cors ) {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Expose-Headers: X-anti-hijack');
		}

		$data += array('error' => 0);

		header('Content-type: text/json');
		if ( $this->antiHijack ) {
			header('X-anti-hijack: ' . strlen($this->antiHijack));
		}

		if ( defined('_START') ) {
			$time = round((microtime(1) - _START)*1000, 4);
			$data['time'] = $time;
			header('X-script-time: ' . $time . ' ms');
		}

		$output = $this->antiHijack . $this->encode($data);
		echo $output;
		exit;
	}

}
