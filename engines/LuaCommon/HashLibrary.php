<?php

// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaHashLibrary extends Scribunto_LuaLibraryBase {

	public function register() {
		$lib = array(
			'listAlgorithms' => array( $this, 'listAlgorithms' ),
			'hashValue' => array( $this, 'hashValue' ),
		);

		return $this->getEngine()->registerInterface( 'mw.hash.lua', $lib );
	}

	/**
	 * Returns a list of known/ supported hash algorithms
	 *
	 * @return string[][]
	 */
	public function listAlgorithms() {
		$algos = hash_algos();
		$algos = array_combine( range( 1, count( $algos ) ), $algos );

		return array( $algos );
	}

	/**
	 * Hash a given value.
	 *
	 * @param string $algo
	 * @param string $value
	 * @return string[]
	 */
	public function hashValue( $algo, $value ) {
		if ( !in_array( $algo, hash_algos() ) ) {
			throw new Scribunto_LuaError( "Unknown hashing algorithm: $algo" );
		}

		$hash = hash( $algo, $value );

		return array( $hash );
	}

}
