<?php
namespace PASAT\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QrCode {
	private const VERSIONS = array(
		array( 'version' => 1, 'size' => 21, 'data' => 19, 'ecc' => 7, 'align' => array() ),
		array( 'version' => 2, 'size' => 25, 'data' => 34, 'ecc' => 10, 'align' => array( 6, 18 ) ),
		array( 'version' => 3, 'size' => 29, 'data' => 55, 'ecc' => 15, 'align' => array( 6, 22 ) ),
		array( 'version' => 4, 'size' => 33, 'data' => 80, 'ecc' => 20, 'align' => array( 6, 26 ) ),
		array( 'version' => 5, 'size' => 37, 'data' => 108, 'ecc' => 26, 'align' => array( 6, 30 ) ),
	);

	public static function matrix( string $text ): ?array {
		$bytes = array_values( unpack( 'C*', $text ) ?: array() );
		$info  = null;

		foreach ( self::VERSIONS as $candidate ) {
			if ( count( $bytes ) <= $candidate['data'] - 2 ) {
				$info = $candidate;
				break;
			}
		}

		if ( null === $info || empty( $bytes ) ) {
			return null;
		}

		$data      = self::data_codewords( $bytes, (int) $info['data'] );
		$ecc       = self::error_codewords( $data, (int) $info['ecc'] );
		$codewords = array_merge( $data, $ecc );
		$size      = (int) $info['size'];
		$matrix    = array_fill( 0, $size, array_fill( 0, $size, false ) );
		$reserved  = array_fill( 0, $size, array_fill( 0, $size, false ) );

		self::function_patterns( $matrix, $reserved, $info );
		self::data_modules( $matrix, $reserved, $info, $codewords );
		self::format_bits( $matrix, $reserved, $size );

		return $matrix;
	}

	private static function data_codewords( array $bytes, int $capacity ): array {
		$bits      = array( 0, 1, 0, 0 );
		$out       = array();
		$pads      = array( 0xec, 0x11 );
		$pad_index = 0;

		self::append_bits( $bits, count( $bytes ), 8 );
		foreach ( $bytes as $byte ) {
			self::append_bits( $bits, (int) $byte, 8 );
		}
		self::append_bits( $bits, 0, min( 4, $capacity * 8 - count( $bits ) ) );

		while ( count( $bits ) % 8 ) {
			$bits[] = 0;
		}

		while ( $bits ) {
			$value = 0;
			for ( $i = 0; $i < 8; ++$i ) {
				$value = ( $value << 1 ) | (int) array_shift( $bits );
			}
			$out[] = $value;
		}

		while ( count( $out ) < $capacity ) {
			$out[] = $pads[ $pad_index % 2 ];
			++$pad_index;
		}

		return $out;
	}

	private static function append_bits( array &$bits, int $value, int $length ): void {
		for ( $i = $length - 1; $i >= 0; --$i ) {
			$bits[] = ( $value >> $i ) & 1;
		}
	}

	private static function error_codewords( array $data, int $degree ): array {
		$generator = array( 1 );
		$ecc       = array_fill( 0, $degree, 0 );

		for ( $i = 0; $i < $degree; ++$i ) {
			$next = array_fill( 0, count( $generator ) + 1, 0 );
			foreach ( $generator as $j => $value ) {
				$next[ $j ]     ^= $value;
				$next[ $j + 1 ] ^= self::gf_mul( $value, self::gf_exp( $i ) );
			}
			$generator = $next;
		}

		foreach ( $data as $byte ) {
			$factor = $byte ^ array_shift( $ecc );
			$ecc[]  = 0;
			for ( $i = 0; $i < $degree; ++$i ) {
				$ecc[ $i ] ^= self::gf_mul( $generator[ $i + 1 ], $factor );
			}
		}

		return $ecc;
	}

	private static function gf_exp( int $power ): int {
		$value = 1;
		for ( $i = 0; $i < $power; ++$i ) {
			$value <<= 1;
			if ( $value & 0x100 ) {
				$value ^= 0x11d;
			}
		}

		return $value;
	}

	private static function gf_mul( int $a, int $b ): int {
		$result = 0;
		while ( $b > 0 ) {
			if ( $b & 1 ) {
				$result ^= $a;
			}
			$a <<= 1;
			if ( $a & 0x100 ) {
				$a ^= 0x11d;
			}
			$b >>= 1;
		}

		return $result;
	}

	private static function set_module( array &$matrix, array &$reserved, int $row, int $col, bool $dark ): void {
		if ( $row < 0 || $col < 0 || $row >= count( $matrix ) || $col >= count( $matrix ) ) {
			return;
		}

		$matrix[ $row ][ $col ]   = $dark;
		$reserved[ $row ][ $col ] = true;
	}

	private static function function_patterns( array &$matrix, array &$reserved, array $info ): void {
		$size = (int) $info['size'];

		self::finder( $matrix, $reserved, 0, 0 );
		self::finder( $matrix, $reserved, $size - 7, 0 );
		self::finder( $matrix, $reserved, 0, $size - 7 );

		for ( $i = 8; $i < $size - 8; ++$i ) {
			self::set_module( $matrix, $reserved, 6, $i, 0 === $i % 2 );
			self::set_module( $matrix, $reserved, $i, 6, 0 === $i % 2 );
		}

		foreach ( $info['align'] as $row ) {
			foreach ( $info['align'] as $col ) {
				if ( empty( $reserved[ $row ][ $col ] ) ) {
					self::alignment( $matrix, $reserved, (int) $row, (int) $col );
				}
			}
		}

		self::set_module( $matrix, $reserved, 4 * (int) $info['version'] + 9, 8, true );

		for ( $i = 0; $i < 9; ++$i ) {
			self::set_module( $matrix, $reserved, 8, $i, false );
			self::set_module( $matrix, $reserved, $i, 8, false );
		}

		for ( $i = 0; $i < 8; ++$i ) {
			self::set_module( $matrix, $reserved, 8, $size - 1 - $i, false );
			self::set_module( $matrix, $reserved, $size - 1 - $i, 8, false );
		}
	}

	private static function finder( array &$matrix, array &$reserved, int $row, int $col ): void {
		for ( $y = -1; $y <= 7; ++$y ) {
			for ( $x = -1; $x <= 7; ++$x ) {
				$dark = $x >= 0 && $x <= 6 && $y >= 0 && $y <= 6 && ( 0 === $x || 6 === $x || 0 === $y || 6 === $y || ( $x >= 2 && $x <= 4 && $y >= 2 && $y <= 4 ) );
				self::set_module( $matrix, $reserved, $row + $y, $col + $x, $dark );
			}
		}
	}

	private static function alignment( array &$matrix, array &$reserved, int $row, int $col ): void {
		for ( $y = -2; $y <= 2; ++$y ) {
			for ( $x = -2; $x <= 2; ++$x ) {
				self::set_module( $matrix, $reserved, $row + $y, $col + $x, max( abs( $x ), abs( $y ) ) !== 1 );
			}
		}
	}

	private static function data_modules( array &$matrix, array $reserved, array $info, array $codewords ): void {
		$bits = array();
		foreach ( $codewords as $word ) {
			self::append_bits( $bits, (int) $word, 8 );
		}

		$size   = (int) $info['size'];
		$index  = 0;
		$upward = true;

		for ( $right = $size - 1; $right >= 1; $right -= 2 ) {
			if ( 6 === $right ) {
				--$right;
			}
			for ( $vertical = 0; $vertical < $size; ++$vertical ) {
				$row = $upward ? $size - 1 - $vertical : $vertical;
				for ( $offset = 0; $offset < 2; ++$offset ) {
					$col = $right - $offset;
					if ( ! empty( $reserved[ $row ][ $col ] ) ) {
						continue;
					}
					$dark = ! empty( $bits[ $index ] );
					if ( 0 === ( $row + $col ) % 2 ) {
						$dark = ! $dark;
					}
					$matrix[ $row ][ $col ] = $dark;
					++$index;
				}
			}
			$upward = ! $upward;
		}
	}

	private static function format_bits( array &$matrix, array &$reserved, int $size ): void {
		$bits = 0x77c4;

		for ( $i = 0; $i < 15; ++$i ) {
			$dark = 1 === ( ( $bits >> $i ) & 1 );

			if ( $i < 6 ) {
				self::set_module( $matrix, $reserved, 8, $i, $dark );
			} elseif ( $i < 8 ) {
				self::set_module( $matrix, $reserved, 8, $i + 1, $dark );
			} else {
				self::set_module( $matrix, $reserved, 8, $size - 15 + $i, $dark );
			}

			if ( $i < 8 ) {
				self::set_module( $matrix, $reserved, $size - $i - 1, 8, $dark );
			} elseif ( $i < 9 ) {
				self::set_module( $matrix, $reserved, 15 - $i, 8, $dark );
			} else {
				self::set_module( $matrix, $reserved, 15 - $i - 1, 8, $dark );
			}
		}
	}
}
