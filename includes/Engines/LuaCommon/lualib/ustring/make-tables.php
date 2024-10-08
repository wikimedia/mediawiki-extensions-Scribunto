#!/usr/bin/php
<?php

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	die( "This script may only be executed from the command line.\n" );
}

$chars = [];
for ( $i = 0; $i <= 0x10ffff; $i++ ) {
	// Skip UTF-16 surrogates
	if ( $i < 0xd800 || $i > 0xdfff ) {
		$chars[$i] = mb_convert_encoding( pack( 'N', $i ), 'UTF-8', 'UTF-32BE' );
	}
}

### Uppercase and Lowercase mappings
echo "Creating upper and lower tables...\n";
$L = fopen( __DIR__ . '/lower.lua', 'w' );
if ( !$L ) {
	die( "Failed to open lower.lua\n" );
}
$U = fopen( __DIR__ . '/upper.lua', 'w' );
if ( !$U ) {
	die( "Failed to open upper.lua\n" );
}
fprintf( $L, "-- This file is automatically generated by make-tables.php\n" );
fprintf( $L, "return {\n" );
fprintf( $U, "-- This file is automatically generated by make-tables.php\n" );
fprintf( $U, "return {\n" );
foreach ( $chars as $i => $c ) {
	$l = mb_strtolower( $c, 'UTF-8' );
	$u = mb_strtoupper( $c, 'UTF-8' );
	if ( $c !== $l ) {
		fprintf( $L, "\t[\"%s\"] = \"%s\",\n", $c, $l );
	}
	if ( $c !== $u ) {
		fprintf( $U, "\t[\"%s\"] = \"%s\",\n", $c, $u );
	}
}
fprintf( $L, "}\n" );
fprintf( $U, "}\n" );
fclose( $L );
fclose( $U );

### Pattern code mappings
echo "Creating charsets table...\n";
$fh = fopen( __DIR__ . '/charsets.lua', 'w' );
if ( !$fh ) {
	die( "Failed to open charsets.lua\n" );
}
$pats = [
	// These should match the expressions in UstringLibrary::patternToRegex()
	'a' => [ '\p{L}', 'lu' ],
	'c' => [ '\p{Cc}', null ],
	'd' => [ '\p{Nd}', null ],
	'l' => [ '\p{Ll}', null ],
	'p' => [ '\p{P}', null ],
	's' => [ '\p{Xps}', null ],
	'u' => [ '\p{Lu}', null ],
	# '[\p{L}\p{Nd}]' exactly matches 'a' + 'd'
	'w' => [ null, 'da' ],
	'x' => [ '[0-9A-Fa-f０-９Ａ-Ｆａ-ｆ]', null ],
	'z' => [ '\0', null ],
];

$ranges = [];

/**
 * @param string $k
 * @param int $start
 * @param int $end
 */
function addRange( $k, $start, $end ) { // phpcs:ignore MediaWiki.NamingConventions.PrefixedGlobalFunctions
	// phpcs:ignore MediaWiki.NamingConventions.ValidGlobalName
	global $fh, $ranges;
	// Speed/memory tradeoff
	if ( !( $start >= 0x20 && $start < 0x7f ) && $end - $start >= 10 ) {
		$ranges[$k][] = sprintf( "c >= 0x%06x and c < 0x%06x", $start, $end );
	} else {
		for ( $i = $start; $i < $end; $i++ ) {
			fprintf( $fh, "\t\t[0x%06x] = 1,\n", $i );
		}
	}
}

fprintf( $fh, "-- This file is automatically generated by make-tables.php\n" );
fprintf( $fh, "local pats = {\n" );
foreach ( $pats as $k => $pp ) {
	$ranges[$k] = [];
	$re = $pp[0];
	if ( !$re ) {
		fprintf( $fh, "\t[0x%02x] = {},\n", ord( $k ) );
		continue;
	}

	$re2 = 'fail';
	if ( $pp[1] ) {
		$re2 = [];
		foreach ( str_split( $pp[1] ) as $p ) {
			$re2[] = $pats[$p][0];
		}
		$re2 = implode( '|', $re2 );
	}

	fprintf( $fh, "\t[0x%02x] = {\n", ord( $k ) );
	$rstart = null;
	foreach ( $chars as $i => $c ) {
		if ( preg_match( "/^$re$/u", $c ) && !preg_match( "/^$re2$/u", $c ) ) {
			$rstart ??= $i;
		} elseif ( $rstart !== null ) {
			addRange( $k, $rstart, $i );
			$rstart = null;
		}
	}
	if ( $rstart !== null ) {
		addRange( $k, $rstart, 0x110000 );
	}
	fprintf( $fh, "\t},\n" );
}
foreach ( $pats as $k => $pp ) {
	$kk = strtoupper( $k );
	fprintf( $fh, "\t[0x%02x] = {},\n", ord( $kk ) );
}
fprintf( $fh, "}\n" );
foreach ( $pats as $k => $pp ) {
	$body = '';
	$check = [];
	if ( $pp[1] ) {
		foreach ( str_split( $pp[1] ) as $p ) {
			$check[] = sprintf( "pats[0x%02x][k]", ord( $p ) );
		}
	}
	// @phan-suppress-next-line PhanImpossibleConditionInGlobalScope
	if ( $ranges[$k] ) {
		$body = "\tlocal c = tonumber( k ) or 0/0;\n";
		$check = array_merge( $check, $ranges[$k] );
	}
	if ( $check ) {
		$body .= "\treturn " . implode( " or\n\t\t", $check );
		fprintf( $fh, "setmetatable( pats[0x%02x], { __index = function ( t, k )\n%s\nend } )\n",
			ord( $k ), $body );
	}
}
foreach ( $pats as $k => $pp ) {
	fprintf( $fh, "setmetatable( pats[0x%02x], { ", ord( strtoupper( $k ) ) );
	fprintf( $fh, "__index = function ( t, k ) return k and not pats[0x%02x][k] end", ord( $k ) );
	fprintf( $fh, " } )\n" );
}
fprintf( $fh, "\n-- For speed, cache printable ASCII characters in main tables\n" );
fprintf( $fh, "for k, t in pairs( pats ) do\n" );
fprintf( $fh, "\tif k >= 0x61 then\n" );
fprintf( $fh, "\t\tfor i = 0x20, 0x7e do\n" );
fprintf( $fh, "\t\t\tt[i] = t[i] or false\n" );
fprintf( $fh, "\t\tend\n" );
fprintf( $fh, "\tend\n" );
fprintf( $fh, "end\n" );
fprintf( $fh, "\nreturn pats\n" );
fclose( $fh );
