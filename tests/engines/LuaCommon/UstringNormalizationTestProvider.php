<?php

require_once( __DIR__ . '/LuaDataProvider.php' );

class UstringNormalizationTestProvider extends LuaDataProvider {
	protected $file = null;
	protected $current = null;

	public static function available( &$message = null ) {
		if ( is_readable( __DIR__ . '/NormalizationTest.txt' ) ) {
			return true;
		}
		$message = wordwrap( 'Download the Unicode Normalization Test Suite from ' .
			'http://unicode.org/Public/UNIDATA/NormalizationTest.txt and save as ' .
			__DIR__ . '/NormalizationTest.txt to run normalization tests. Note that ' .
			'running these tests takes quite some time.' );
		return false;
	}

	public function __construct( $engine ) {
		parent::__construct( $engine, 'UstringNormalizationTests' );
		if ( UstringNormalizationTestProvider::available() ) {
			$this->file = fopen( __DIR__ . '/NormalizationTest.txt', 'r' );
			$this->rewind();
		}
	}

	public function destory() {
		if ( $this->file ) {
			fclose( $this->file );
			$this->file = null;
		}
		parent::destory();
	}

	public function rewind() {
		if ( $this->file ) {
			rewind($this->file);
			$this->key = 0;
			$this->next();
		}
	}

	public function valid() {
		return $this->file && !feof($this->file);
	}

	public function current() {
		return $this->current;
	}

	public function next() {
		$this->current = array( null, null, null, null, null, null );
		while( $this->valid() ) {
			$this->key++;
			$line = fgets( $this->file );
			if ( preg_match( '/^((?:[0-9A-F ]+;){5})/', $line, $m ) ) {
				$line = rtrim( $m[1], ';' );
				$ret = array( $line );
				foreach ( explode( ';', $line ) as $field ) {
					$args = array( 'N*' );
					foreach ( explode( ' ', $field ) as $char ) {
						$args[] = hexdec( $char );
					}
					$s = call_user_func_array( 'pack', $args );
					$s = mb_convert_encoding( $s, 'UTF-8', 'UTF-32BE' );
					$ret[] = $s;
				}
				$this->current = $ret;
				return;
			}
		}
	}

	public function run( $c1, $c2, $c3, $c4, $c5 ) {
		return $this->engine->getInterpreter()->callFunction( $this->exports['run'],
			$c1, $c2, $c3, $c4, $c5
		);
	}
}
