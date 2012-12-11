<?php

class Scribunto_LuaUstringModule {
	var $engine;

	function __construct( $engine ) {
		$this->engine = $engine;
	}

	function register( $pureLua ) {
		if ( $pureLua ) {
			$lib = array(
				// Pattern matching is still much faster in PHP, even with the
				// overhead of LuaStandalone
				'find' => array( $this, 'ustringFind' ),
				'match' => array( $this, 'ustringMatch' ),
				'gmatch' => array( $this, 'ustringGmatch' ),
				'gsub' => array( $this, 'ustringGsub' ),
			);
		} else {
			$lib = array(
				'isutf8' => array( $this, 'ustringIsUtf8' ),
				'byteoffset' => array( $this, 'ustringByteoffset' ),
				'codepoint' => array( $this, 'ustringCodepoint' ),
				'toNFC' => array( $this, 'ustringToNFC' ),
				'toNFD' => array( $this, 'ustringToNFD' ),
				'char' => array( $this, 'ustringChar' ),
				'len' => array( $this, 'ustringLen' ),
				'sub' => array( $this, 'ustringSub' ),
				'upper' => array( $this, 'ustringUpper' ),
				'lower' => array( $this, 'ustringLower' ),
				'find' => array( $this, 'ustringFind' ),
				'match' => array( $this, 'ustringMatch' ),
				'gmatch' => array( $this, 'ustringGmatch' ),
				'gsub' => array( $this, 'ustringGsub' ),
			);
		}
		$this->engine->registerInterface( 'mw.ustring.lua', $lib );
	}

	public function ustringIsUtf8( $s ) {
		return array( mb_check_encoding( $s, 'UTF-8' ) );
	}

	public function ustringByteoffset( $s, $l = 1, $i = 1 ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}

		$bytelen = strlen( $s );
		if ( $i < 0 ) {
			$i = $bytelen + $i - 1;
		}
		if ( $i < 1 || $i > $bytelen ) {
			return array( null );
		}
		$i--;
		$j = $i;
		while ( ( ord( $s[$i] ) & 0xc0 ) === 0x80 ) {
			$i--;
		}
		if ( $l > 0 && $j === $i ) {
			$l--;
		}
		$char = mb_strlen( substr( $s, 0, $i ), 'UTF-8' ) + $l;
		if ( $char < 0 || $char >= mb_strlen( $s, 'UTF-8' ) ) {
			return array( null );
		} else {
			return array( strlen( mb_substr( $s, 0, $char, 'UTF-8' ) ) + 1 );
		}
	}

	public function ustringCodepoint( $s, $i = 1, $j = null ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}

		$l = mb_strlen( $s, 'UTF-8' );
		if ( $i < 0 ) {
			$i = $l + $i + 1;
		}
		if ( $j === null ) {
			$j = $i;
		}
		if ( $j < 0 ) {
			$j = $l + $j + 1;
		}
		$i = max( 1, min( $i, $l ) );
		$j = max( 1, min( $j, $l ) );
		$s = mb_substr( $s, $i - 1, $j - $i + 1, 'UTF-8' );
		return unpack( 'N*', mb_convert_encoding( $s, 'UTF-32BE', 'UTF-8' ) );
	}

	public function ustringToNFC( $s ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			return array( null );
		}
		return array( UtfNormal::toNFC( $s ) );
	}

	public function ustringToNFD( $s ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			return array( null );
		}
		return array( UtfNormal::toNFD( $s ) );
	}

	public function ustringChar() {
		$args = func_get_args();
		foreach ( $args as $k=>&$v ) {
			if ( !is_numeric( $v ) ) {
				$k++;
				throw new Scribunto_LuaError( "bad argument #$k to 'char' (number expected, got " . gettype( $v ) . ")" );
			}
			$v = (int)floor( $v );
			if ( $v < 0 || $v > 0x10ffff ) {
				$k++;
				throw new Scribunto_LuaError( "bad argument #$k to 'char' (value out of range)" );
			}
		}
		array_unshift( $args, 'N*' );
		$s = call_user_func_array( 'pack', $args );
		return array( mb_convert_encoding( $s, 'UTF-8', 'UTF-32BE' ) );
	}

	public function ustringLen( $s ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			return array( null );
		}
		return array( mb_strlen( $s, 'UTF-8' ) );
	}

	public function ustringSub( $s, $i=1, $j=-1 ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}

		$len = mb_strlen( $s, 'UTF-8' );
		if ( $i < 0 ) {
			$i = $len + $i + 1;
		}
		if ( $j < 0 ) {
			$j = $len + $j + 1;
		}
		$i = max( 1, min( $i, $len + 1 ) );
		$j = max( 1, min( $j, $len + 1 ) );
		$s = mb_substr( $s, $i - 1, $j - $i + 1, 'UTF-8' );
		return array( $s );
	}

	public function ustringUpper( $s ) {
		return array( mb_strtoupper( $s, 'UTF-8' ) );
	}

	public function ustringLower( $s ) {
		return array( mb_strtolower( $s, 'UTF-8' ) );
	}

	/* Convert a Lua pattern into a PCRE regex */
	private function patternToRegex( $pattern ) {
		if ( !mb_check_encoding( $pattern, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "Pattern is not UTF-8" );
		}

		$pat = preg_split( '//us', $pattern, null, PREG_SPLIT_NO_EMPTY );

		static $charsets = null, $brcharsets = null;
		if ( $charsets === null ) {
			$charsets = array(
				'a' => '\p{L}',
				'c' => '\p{Cc}',
				'd' => '\p{Nd}',
				'l' => '\p{Ll}',
				'p' => '\p{P}',
				's' => '\p{Xps}',
				'u' => '\p{Lu}',
				'w' => '[\p{L}\p{Nd}]',
				'x' => '[0-9A-Fa-f０-９Ａ-Ｆａ-ｆ]',
				'z' => '\0',
				'A' => '\P{L}',
				'C' => '\P{Cc}',
				'D' => '\P{Nd}',
				'L' => '\P{Ll}',
				'P' => '\P{P}',
				'S' => '\P{Xps}',
				'U' => '\P{Lu}',
				'W' => '[\P{L}\P{Nd}]',
				'X' => '[^0-9A-Fa-f０-９Ａ-Ｆａ-ｆ]',
				'Z' => '[^\0]',
			);
			$brcharsets = array(
				'w' => '\p{L}\p{Nd}',
				'x' => '0-9A-Fa-f０-９Ａ-Ｆａ-ｆ',

				// Negated sets that are not expressable as a simple \P{} are
				// unfortunately complicated.

				// Xan is L plus N, so ^Xan plus Nl plus No is anything that's not L or Nd
				'W' => '\P{Xan}\p{Nl}\p{No}',

				// Manually constructed. Fun.
				'X' => '\x00-\x2f\x3a-\x40\x47-\x60\x67-\x{ff0f}'
					. '\x{ff1a}-\x{ff20}\x{ff27}-\x{ff40}\x{ff47}-\x{10ffff}',

				// Ha!
				'Z' => '\x01-\x{10ffff}',
			) + $charsets;
		}

		$re = '/';
		$len = count( $pat );
		$capt = array();
		$anypos = false;
		$captparen = array();
		$opencapt = array();
		$bct = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$ii = $i + 1;
			$q = false;
			switch ( $pat[$i] ) {
			case '^':
				$q = $i;
				$re .= $q ? '\\^' : '^';
				break;

			case '$':
				$q = ( $i < $len - 1 );
				$re .= $q ? '\\$' : '$';
				break;

			case '(':
				if ( $i + 1 >= $len ) {
					throw new Scribunto_LuaError( "Unmatched open-paren at pattern character $ii" );
				}
				$n = count( $capt ) + 1;
				$capt[$n] = ( $pat[$i + 1] === ')' );
				if ( $capt[$n] ) {
					$anypos = true;
				}
				$re .= "(?<m$n>";
				$opencapt[] = $n;
				$captparen[$n] = $ii;
				break;

			case ')':
				if ( count( $opencapt ) <= 0 ) {
					throw new Scribunto_LuaError( "Unmatched close-paren at pattern character $ii" );
				}
				array_pop( $opencapt );
				$re .= $pat[$i];
				break;

			case '%':
				$i++;
				if ( $i >= $len ) {
					throw new Scribunto_LuaError( "malformed pattern (ends with '%')" );
				}
				if ( isset( $charsets[$pat[$i]] ) ) {
					$re .= $charsets[$pat[$i]];
					$q = true;
				} elseif ( $pat[$i] === 'b' ) {
					if ( $i + 2 >= $len ) {
						throw new Scribunto_LuaError( "malformed pattern (missing arguments to \'%b\')" );
					}
					$d1 = preg_quote( $pat[++$i], '/' );
					$d2 = preg_quote( $pat[++$i], '/' );
					if ( $d1 === $d2 ) {
						$re .= "{$d1}[^$d1]*$d1";
					} else {
						$bct++;
						$re .= "(?<b$bct>$d1(?:(?>[^$d1$d2]+)|(?P>b$bct))*$d2)";
					}
				} elseif ( $pat[$i] >= '0' && $pat[$i] <= '9' ) {
					$n = ord( $pat[$i] ) - 0x30;
					if ( $n === 0 || $n > count( $capt ) || in_array( $n, $opencapt ) ) {
						throw new Scribunto_LuaError( "invalid capture index %$n at pattern character $ii" );
					}
					$re .= "\\g{m$n}";
				} else {
					$re .= preg_quote( $pat[$i], '/' );
					$q = true;
				}
				break;

			case '[':
				$re .= '[';
				$i++;
				if ( $i < $len && $pat[$i] === '^' ) {
					$re .= '^';
					$i++;
				}
				for ( ; $i < $len && $pat[$i] !== ']'; $i++ ) {
					if ( $pat[$i] === '%' ) {
						$i++;
						if ( $i >= $len ) {
							break;
						}
						if ( isset( $brcharsets[$pat[$i]] ) ) {
							$re .= $brcharsets[$pat[$i]];
						} else {
							$re .= preg_quote( $pat[$i], '/' );
						}
					} elseif( $i + 2 < $len && $pat[$i + 1] === '-' && $pat[$i + 2] !== ']' ) {
						$re .= preg_quote( $pat[$i], '/' ) . '-' . preg_quote( $pat[$i+2], '/' );
						$i += 2;
					} else {
						$re .= preg_quote( $pat[$i], '/' );
					}
				}
				if ( $i >= $len ) {
					throw new Scribunto_LuaError( "Missing close-bracket for character set beginning at pattern character $ii" );
				}
				$re .= ']';
				$q = true;
				break;

			case ']':
				throw new Scribunto_LuaError( "Unmatched close-bracket at pattern character $ii" );

			case '.':
				$re .= $pat[$i];
				$q = true;
				break;

			default:
				$re .= preg_quote( $pat[$i], '/' );
				$q = true;
				break;
			}
			if ( $q && $i + 1 < $len ) {
				switch ( $pat[$i + 1] ) {
				case '*':
				case '+':
				case '?':
					$re .= $pat[++$i];
					break;
				case '-':
					$re .= '*?';
					$i++;
					break;
				}
			}
		}
		if ( count( $opencapt ) ) {
			$ii = $captparen[$opencapt[0]];
			throw new Scribunto_LuaError( "Unclosed capture beginning at pattern character $ii" );
		}
		$re .= '/us';
		return array( $re, $capt, $anypos );
	}

	private function addCapturesFromMatch( $arr, $s, $m, $capt, $offset, $m0_if_no_captures ) {
		if ( count( $capt ) ) {
			foreach ( $capt as $n => $pos ) {
				if ( $pos ) {
					$o = mb_strlen( substr( $s, 0, $m["m$n"][1] ), 'UTF-8' ) + $offset;
					$arr[] = $o;
				} else {
					$arr[] = $m["m$n"][0];
				}
			}
		} elseif ( $m0_if_no_captures ) {
			$arr[] = $m[0][0];
		}
		return $arr;
	}

	public function ustringFind( $s, $pattern, $init = 1, $plain = false ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}
		if ( !mb_check_encoding( $pattern, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "Pattern is not UTF-8" );
		}
		$len = mb_strlen( $s, 'UTF-8' );
		if ( $init < 0 ) {
			$init = $len + $init + 1;
		}

		if ( $init > 1 ) {
			$s = mb_substr( $s, $init - 1, $len - $init + 1, 'UTF-8' );
		} else {
			$init = 1;
		}

		if ( $plain ) {
			$ret = mb_strpos( $s, $pattern, 0, 'UTF-8' );
			return array( ( $ret === false ) ? null : $ret + $init );
		}

		list( $re, $capt ) = $this->patternToRegex( $pattern );
		if ( !preg_match( $re, $s, $m, PREG_OFFSET_CAPTURE ) ) {
			return array( null );
		}
		$o = mb_strlen( substr( $s, 0, $m[0][1] ), 'UTF-8' ) + $init;
		$ret = array( $o, $o + mb_strlen( $m[0][0], 'UTF-8' ) - 1 );
		return $this->addCapturesFromMatch( $ret, $s, $m, $capt, $init, false );
	}

	public function ustringMatch( $s, $pattern, $init = 1 ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}
		if ( !mb_check_encoding( $pattern, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "Pattern is not UTF-8" );
		}
		$len = mb_strlen( $s, 'UTF-8' );
		if ( $init < 0 ) {
			$init = $len + $init + 1;
		}
		if ( $init > 1 ) {
			$s = mb_substr( $s, $init - 1, $len - $init + 1, 'UTF-8' );
		} else {
			$init = 1;
		}

		list( $re, $capt ) = $this->patternToRegex( $pattern );
		if ( !preg_match( $re, $s, $m, PREG_OFFSET_CAPTURE ) ) {
			return array( null );
		}
		return $this->addCapturesFromMatch( array(), $s, $m, $capt, $init, true );
	}

	public function ustringGmatch( $s, $pattern ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}
		if ( !mb_check_encoding( $pattern, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "Pattern is not UTF-8" );
		}

		$interpreter = $this->engine->getInterpreter();

		if ( $pattern[0] === '^' ) {
			return array( $interpreter->wrapPhpFunction( function () {
				return array( null );
			} ), null, null );
		}

		list( $re, $capt ) = $this->patternToRegex( $pattern );
		$pos = 0;
		$len = mb_strlen( $s, 'UTF-8' );

		return array( $interpreter->wrapPhpFunction( function () use ( $s, $re, $capt, &$pos ) {
			if ( !preg_match( $re, $s, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
				return array( null );
			}
			$pos = $m[0][1] + strlen( $m[0][0] );
			return $this->addCapturesFromMatch( array(), $s, $m, $capt, 1, true );
		} ), null, null );
	}

	public function ustringGsub( $s, $pattern, $repl, $n = -1 ) {
		if ( !mb_check_encoding( $s, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "String is not UTF-8" );
		}
		if ( !mb_check_encoding( $pattern, 'UTF-8' ) ) {
			throw new Scribunto_LuaError( "Pattern is not UTF-8" );
		}
		if ( $n === null ) {
			$n = -1;
		}

		list( $re, $capt, $anypos ) = $this->patternToRegex( $pattern );
		$captures = array();

		if ( $anypos ) {
			// preg_replace_callback doesn't take a "flags" argument, so we
			// can't pass PREG_OFFSET_CAPTURE to it, which is needed to handle
			// position captures. So instead we have to do a preg_match_all and
			// handle the captures ourself.
			$ct = preg_match_all( $re, $s, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
			if ( $n >= 0 ) {
				$ct = min( $ct, $n );
			}
			for ( $i = 0; $i < $ct; $i++ ) {
				$m = $mm[$i];
				$c = array( $m[0][0] );
				foreach ( $this->addCapturesFromMatch( array(), $s, $m, $capt, 1, false ) as $k => $v ) {
					$k++;
					$c["m$k"] = $v;
				}
				$captures[] = $c;
			}
		}

		switch ( gettype( $repl ) ) {
		case 'string':
			$cb = function ( $m ) use ( $repl, $anypos, &$captures ) {
				if ( $anypos ) {
					$m = array_shift( $captures );
				}
				return preg_replace_callback( '/%([%0-9])/', function ( $m2 ) use ( $m ) {
					$x = $m2[1];
					if ( $x === '%' ) {
						return '%';
					} elseif ( $x === '0' ) {
						return $m[0];
					} elseif ( isset( $m["m$x"] ) ) {
						return $m["m$x"];
					} else {
						throw new Scribunto_LuaError( "invalid capture index %$x in replacement string" );
					}
				}, $repl );
			};
			break;

		case 'array':
			$cb = function ( $m ) use ( $repl, $anypos, &$captures ) {
				if ( $anypos ) {
					$m = array_shift( $captures );
				}
				$x = isset( $m['m1'] ) ? $m['m1'] : $m[0];
				return isset( $repl[$x] ) ? $repl[$x] : $m[0];
			};
			break;

		case 'object':
			$interpreter = $this->engine->getInterpreter();
			if ( $interpreter->isLuaFunction( $repl ) ) {
				$cb = function ( $m ) use ( $interpreter, $capt, $repl, $anypos, &$captures ) {
					if ( $anypos ) {
						$m = array_shift( $captures );
					}
					$args = array( $repl );
					if ( count( $capt ) ) {
						foreach ( $capt as $i => $pos ) {
							$args[] = $m["m$i"];
						}
					} else {
						$args[] = $m[0];
					}
					$ret = call_user_func_array( array( $interpreter, 'callFunction' ), $args );
					if ( count( $ret ) === 0 || $ret[0] === null ) {
						return $m[0];
					}
					return $ret[0];
				};
			} else {
				throw new Scribunto_LuaError(
					'Invalid argument type object of class ' . get_class( $repl ) . ' for repl'
				);
			}
			break;

		default:
			throw new Scribunto_LuaError( 'Invalid argument type ' . gettype( $repl ) . ' for repl' );
		}

		$count = 0;
		$s2 = preg_replace_callback( $re, $cb, $s, $n, $count );
		return array( $s2, $count );
	}
}
