<?php

namespace WP_CLI\Iterators;

use Countable;
use Iterator;
use ReturnTypeWillChange;
use SplFileObject;
use WP_CLI;

/**
 * Allows incrementally reading and parsing lines from a CSV file.
 *
 * @implements \Iterator<int, string>
 */
class CSV implements Countable, Iterator {

	const ROW_SIZE = 4096;

	private $filename;
	private $file_pointer;

	private $delimiter;
	private $columns;

	private $current_index;
	private $current_element;

	public function __construct( $filename, $delimiter = ',' ) {
		$this->filename     = $filename;
		$this->file_pointer = fopen( $filename, 'rb' );
		if ( ! $this->file_pointer ) {
			WP_CLI::error( sprintf( 'Could not open file: %s', $filename ) );
		}

		$this->delimiter = $delimiter;
	}

	#[ReturnTypeWillChange]
	public function rewind() {
		rewind( $this->file_pointer );

		$this->columns = fgetcsv( $this->file_pointer, self::ROW_SIZE, $this->delimiter, '"', '\\' );

		$this->current_index = -1;
		$this->next();
	}

	#[ReturnTypeWillChange]
	public function current() {
		return $this->current_element;
	}

	#[ReturnTypeWillChange]
	public function key() {
		return $this->current_index;
	}

	#[ReturnTypeWillChange]
	public function next() {
		$this->current_element = false;

		while ( true ) {
			$row = fgetcsv( $this->file_pointer, self::ROW_SIZE, $this->delimiter, '"', '\\' );

			if ( false === $row ) {
				break;
			}

			$element = [];
			foreach ( $this->columns as $i => $key ) {
				if ( isset( $row[ $i ] ) ) {
					$element[ $key ] = $row[ $i ];
				}
			}

			if ( ! empty( $element ) ) {
				$this->current_element = $element;
				++$this->current_index;

				break;
			}
		}
	}

	/**
	 * @return int
	 */
	#[ReturnTypeWillChange]
	public function count() {
		$file = new SplFileObject( $this->filename, 'r' );
		$file->seek( PHP_INT_MAX );
		return $file->key() + 1;
	}

	#[ReturnTypeWillChange]
	public function valid() {
		return is_array( $this->current_element );
	}
}
