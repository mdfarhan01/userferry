<?php
/**
 * Reads an uploaded CSV, XLSX, JSON or XML file and normalises it into a
 * temporary JSON-lines file (one object per row, keyed by column name), so
 * the import job can process any format the same way, in batches.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions -- Streaming large files row by row needs native file handles; all paths are the upload's temp file or the plugin's own protected folder.

if ( ! class_exists( 'UserFerry_Import_Reader' ) ) {

	/**
	 * Class UserFerry_Import_Reader
	 */
	class UserFerry_Import_Reader {

		/**
		 * Number of sample rows returned for the mapping screen.
		 */
		const SAMPLE_ROWS = 3;

		/**
		 * Safety limits (filterable). They protect PHP memory and time limits
		 * against very large or deliberately crafted files.
		 *
		 * @return array
		 */
		public static function limits() {
			/**
			 * Filters the import safety limits.
			 *
			 * @param array $limits {
			 *     @type int $max_rows          Maximum data rows per file.
			 *     @type int $max_columns       Maximum columns.
			 *     @type int $max_cell_bytes    Maximum length of one cell.
			 *     @type int $max_json_bytes    Maximum JSON file size (JSON is decoded in memory).
			 *     @type int $max_xlsx_sheet    Maximum uncompressed worksheet size.
			 *     @type int $max_xlsx_strings  Maximum uncompressed shared-strings XML size.
			 *     @type int $max_xlsx_meta     Maximum uncompressed size of workbook.xml and its relationships.
			 *     @type int $max_xlsx_total    Maximum uncompressed size of the whole .xlsx package.
			 *     @type int $max_xlsx_entries  Maximum number of files inside the .xlsx package.
			 *     @type int $max_xlsx_ratio    Maximum compression ratio of any entry (zip-bomb guard).
			 * }
			 */
			return wp_parse_args(
				(array) apply_filters( 'userferry_import_limits', array() ),
				array(
					'max_rows'         => 100000,
					'max_columns'      => 250,
					'max_cell_bytes'   => 65535,
					'max_json_bytes'   => 20 * MB_IN_BYTES,
					'max_xlsx_sheet'   => 200 * MB_IN_BYTES,
					'max_xlsx_strings' => 16 * MB_IN_BYTES,
					'max_xlsx_meta'    => 2 * MB_IN_BYTES,
					'max_xlsx_total'   => 250 * MB_IN_BYTES,
					'max_xlsx_entries' => 2000,
					'max_xlsx_ratio'   => 200,
				)
			);
		}

		/**
		 * How many bytes the shared-strings table may use in memory: the
		 * configured limit, but never more than a quarter of the PHP memory
		 * still available, so small hosting plans are safe too.
		 *
		 * @param int $limit Configured maximum.
		 * @return int
		 */
		private static function memory_budget( $limit ) {
			$memory = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
			if ( $memory <= 0 ) {
				$memory = 512 * MB_IN_BYTES; // -1 means unlimited; still be conservative.
			}
			$available = max( 0, $memory - memory_get_usage( true ) );
			return (int) max( 4 * MB_IN_BYTES, min( (int) $limit, (int) ( $available / 4 ) ) );
		}

		/**
		 * Error for a file wider than the column limit (never truncate silently).
		 *
		 * @param array $limits Limits.
		 * @return WP_Error
		 */
		private static function too_many_columns( array $limits ) {
			/* translators: %s: maximum number of columns */
			return new WP_Error( 'userferry_import_columns', sprintf( __( 'This file has more than %s columns. Remove unused columns and try again.', 'userferry' ), number_format_i18n( $limits['max_columns'] ) ) );
		}

		/**
		 * Error for a row that has values in columns without a name.
		 *
		 * @param int $row Spreadsheet row number.
		 * @return WP_Error
		 */
		private static function unnamed_values( $row ) {
			/* translators: %s: row number */
			return new WP_Error( 'userferry_import_unnamed', sprintf( __( 'Row %s has values in columns that have no name in the first row. Add a column name for every column that contains data, then import again.', 'userferry' ), number_format_i18n( $row ) ) );
		}

		/**
		 * Supported extensions.
		 *
		 * @return string[]
		 */
		public static function extensions() {
			return array( 'csv', 'xlsx', 'json', 'xml' );
		}

		/**
		 * Parse $source into $part.
		 *
		 * @param string $source Source file path.
		 * @param string $ext    Extension (csv|xlsx|json|xml).
		 * @param string $part   Target JSON-lines file.
		 * @return array|WP_Error { headers: string[], total: int, sample: array[] }
		 */
		public static function parse( $source, $ext, $part ) {
			$out = fopen( $part, 'wb' );
			if ( ! $out ) {
				return new WP_Error( 'userferry_import_write', __( 'Could not write to the plugin’s folder inside wp-content/uploads.', 'userferry' ) );
			}

			$state = array(
				'out'     => $out,
				'headers' => array(),
				'total'   => 0,
				'sample'  => array(),
				'limits'  => self::limits(),
				'error'   => null,
			);

			switch ( $ext ) {
				case 'xlsx':
					$result = self::read_xlsx( $source, $state );
					break;
				case 'json':
					$result = self::read_json( $source, $state );
					break;
				case 'xml':
					$result = self::read_xml( $source, $state );
					break;
				default:
					$result = self::read_csv( $source, $state );
			}

			fclose( $out );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( $state['error'] instanceof WP_Error ) {
				return $state['error'];
			}
			if ( empty( $state['headers'] ) || $state['total'] < 1 ) {
				return new WP_Error( 'userferry_import_empty', __( 'No rows were found in this file. Make sure the first row contains column names and the file has at least one user.', 'userferry' ) );
			}

			return array(
				'headers' => $state['headers'],
				'total'   => $state['total'],
				'sample'  => $state['sample'],
			);
		}

		/**
		 * Store one row. Returns false (and records an error) when a limit is hit,
		 * so readers stop immediately.
		 *
		 * @param array $row   Associative row (column => value).
		 * @param array $state Reader state (by reference).
		 * @return bool
		 */
		private static function emit( array $row, array &$state ) {
			$limits = $state['limits'];
			$clean  = array();
			$empty  = true;

			if ( count( $row ) > $limits['max_columns'] ) {
				/* translators: %s: maximum number of columns */
				$state['error'] = new WP_Error( 'userferry_import_columns', sprintf( __( 'This file has more than %s columns. Remove unused columns and try again.', 'userferry' ), number_format_i18n( $limits['max_columns'] ) ) );
				return false;
			}

			foreach ( $row as $column => $value ) {
				$column = (string) $column;
				if ( '' === $column ) {
					continue;
				}
				$value = is_scalar( $value ) || null === $value ? (string) $value : (string) wp_json_encode( $value );
				if ( strlen( $value ) > $limits['max_cell_bytes'] ) {
					$state['error'] = new WP_Error(
						'userferry_import_cell',
						/* translators: 1: row number, 2: column name, 3: maximum size */
						sprintf( __( 'Row %1$s, column “%2$s” is longer than the %3$s limit for one cell.', 'userferry' ), number_format_i18n( $state['total'] + 2 ), $column, size_format( $limits['max_cell_bytes'] ) )
					);
					return false;
				}
				$value = self::to_utf8( $value );
				if ( '' !== trim( $value ) ) {
					$empty = false;
				}
				if ( ! in_array( $column, $state['headers'], true ) ) {
					if ( count( $state['headers'] ) >= $limits['max_columns'] ) {
						/* translators: %s: maximum number of columns */
						$state['error'] = new WP_Error( 'userferry_import_columns', sprintf( __( 'This file has more than %s columns. Remove unused columns and try again.', 'userferry' ), number_format_i18n( $limits['max_columns'] ) ) );
						return false;
					}
					$state['headers'][] = $column;
				}
				$clean[ $column ] = $value;
			}
			if ( $empty ) {
				return true;
			}

			if ( $state['total'] >= $limits['max_rows'] ) {
				/* translators: %s: maximum number of rows */
				$state['error'] = new WP_Error( 'userferry_import_rows', sprintf( __( 'This file has more than %s rows. Split it into smaller files and import them one after another.', 'userferry' ), number_format_i18n( $limits['max_rows'] ) ) );
				return false;
			}

			fwrite( $state['out'], wp_json_encode( $clean ) . "\n" );
			++$state['total'];
			if ( count( $state['sample'] ) < self::SAMPLE_ROWS ) {
				$state['sample'][] = $clean;
			}
			return true;
		}

		/**
		 * Make a string valid UTF-8 (files saved by older Excel versions are often Windows-1252).
		 *
		 * @param string $value Value.
		 * @return string
		 */
		private static function to_utf8( $value ) {
			if ( '' === $value || ( function_exists( 'mb_check_encoding' ) ? mb_check_encoding( $value, 'UTF-8' ) : '' !== wp_check_invalid_utf8( $value ) ) ) {
				return $value;
			}
			if ( function_exists( 'mb_convert_encoding' ) ) {
				return (string) mb_convert_encoding( $value, 'UTF-8', 'Windows-1252' );
			}
			return wp_check_invalid_utf8( $value, true );
		}

		/**
		 * Make column names unique and non-empty.
		 *
		 * @param array $headers Raw headers.
		 * @return string[]
		 */
		private static function normalise_headers( array $headers ) {
			$out  = array();
			$seen = array();
			foreach ( array_values( $headers ) as $i => $header ) {
				$header = trim( self::to_utf8( (string) $header ) );
				$header = preg_replace( '/^\xEF\xBB\xBF/', '', $header );
				if ( '' === $header ) {
					/* translators: %d: column number */
					$header = sprintf( __( 'Column %d', 'userferry' ), $i + 1 );
				}
				$base = $header;
				$n    = 2;
				while ( isset( $seen[ $header ] ) ) {
					$header = $base . ' (' . $n . ')';
					++$n;
				}
				$seen[ $header ] = true;
				$out[]           = $header;
			}
			return $out;
		}

		/**
		 * Undo the formula-protection apostrophe added by this plugin's CSV export.
		 *
		 * @param string $value Value.
		 * @return string
		 */
		private static function unprotect( $value ) {
			if ( strlen( $value ) > 1 && "'" === $value[0] && false !== strpos( "=+-@\t\r", $value[1] ) ) {
				return substr( $value, 1 );
			}
			return $value;
		}

		/**
		 * CSV reader with delimiter detection (comma, semicolon, tab).
		 *
		 * @param string $source File.
		 * @param array  $state  State.
		 * @return true|WP_Error
		 */
		private static function read_csv( $source, array &$state ) {
			$in = fopen( $source, 'rb' );
			if ( ! $in ) {
				return new WP_Error( 'userferry_import_read', __( 'The uploaded file could not be read.', 'userferry' ) );
			}

			$first = (string) fgets( $in );
			$first = preg_replace( '/^\xEF\xBB\xBF/', '', $first );
			$best  = ',';
			$max   = -1;
			foreach ( array( ',', ';', "\t" ) as $candidate ) {
				$count = count( str_getcsv( $first, $candidate, '"', '\\' ) );
				if ( $count > $max ) {
					$max  = $count;
					$best = $candidate;
				}
			}

			rewind( $in );
			if ( "\xEF\xBB\xBF" !== fread( $in, 3 ) ) {
				rewind( $in );
			}

			$headers = fgetcsv( $in, 0, $best, '"', '\\' );
			if ( ! is_array( $headers ) ) {
				fclose( $in );
				return true;
			}
			if ( count( $headers ) > $state['limits']['max_columns'] ) {
				fclose( $in );
				/* translators: %s: maximum number of columns */
				return new WP_Error( 'userferry_import_columns', sprintf( __( 'This file has more than %s columns. Remove unused columns and try again.', 'userferry' ), number_format_i18n( $state['limits']['max_columns'] ) ) );
			}
			$headers = self::normalise_headers( $headers );

			while ( true ) {
				// Length 0 = no line-length limit (supported since PHP 5.1, so fine on PHP 7.4).
				$line = fgetcsv( $in, 0, $best, '"', '\\' );
				if ( false === $line ) {
					break;
				}
				if ( array( null ) === $line ) {
					continue;
				}
				if ( count( $line ) > $state['limits']['max_columns'] ) {
					fclose( $in );
					return self::too_many_columns( $state['limits'] );
				}
				foreach ( array_slice( $line, count( $headers ) ) as $extra ) {
					if ( '' !== trim( (string) $extra ) ) {
						fclose( $in );
						return self::unnamed_values( $state['total'] + 2 );
					}
				}
				$row = array();
				foreach ( $headers as $i => $header ) {
					$row[ $header ] = isset( $line[ $i ] ) ? self::unprotect( (string) $line[ $i ] ) : '';
				}
				if ( ! self::emit( $row, $state ) ) {
					break;
				}
			}

			fclose( $in );
			return true;
		}

		/**
		 * JSON reader: an array of objects (or { "users": [...] }).
		 *
		 * @param string $source File.
		 * @param array  $state  State.
		 * @return true|WP_Error
		 */
		private static function read_json( $source, array &$state ) {
			$size = (int) filesize( $source );
			if ( $size > $state['limits']['max_json_bytes'] ) {
				return new WP_Error(
					'userferry_import_json_size',
					/* translators: %s: maximum file size */
					sprintf( __( 'JSON files larger than %s cannot be imported safely. Export as CSV instead — CSV is read row by row, so it has no size limit beyond your upload limit.', 'userferry' ), size_format( $state['limits']['max_json_bytes'] ) )
				);
			}
			$data = json_decode( (string) file_get_contents( $source ), true, 64 );
			if ( ! is_array( $data ) ) {
				return new WP_Error( 'userferry_import_json', __( 'This is not valid JSON. The file should contain an array of user objects.', 'userferry' ) );
			}
			if ( isset( $data['users'] ) && is_array( $data['users'] ) ) {
				$data = $data['users'];
			}
			foreach ( $data as $item ) {
				if ( is_array( $item ) && ! self::emit( $item, $state ) ) {
					break;
				}
			}
			unset( $data );
			return true;
		}

		/**
		 * XML reader: every child of the root element is one user; its child
		 * elements are the columns.
		 *
		 * @param string $source File.
		 * @param array  $state  State.
		 * @return true|WP_Error
		 */
		private static function read_xml( $source, array &$state ) {
			if ( ! class_exists( 'XMLReader' ) || ! class_exists( 'DOMDocument' ) ) {
				return new WP_Error( 'userferry_import_xml', __( 'XML import needs the PHP XMLReader and DOM extensions.', 'userferry' ) );
			}

			$reader = new XMLReader();
			if ( ! $reader->open( $source, null, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING ) ) {
				return new WP_Error( 'userferry_import_xml', __( 'This XML file could not be opened.', 'userferry' ) );
			}

			$doc   = new DOMDocument();
			$found = false;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- XMLReader properties.
			while ( @$reader->read() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed XML is reported below instead of raising warnings.
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( XMLReader::ELEMENT !== $reader->nodeType || 1 !== $reader->depth ) {
					continue;
				}
				$node = $reader->expand( $doc );
				if ( ! $node ) {
					continue;
				}
				$found = true;
				$row   = array();
				foreach ( $node->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if ( XML_ELEMENT_NODE === $child->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$row[ $child->nodeName ] = $child->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}
				if ( ! self::emit( $row, $state ) ) {
					break;
				}
			}
			$reader->close();

			if ( ! $found ) {
				return new WP_Error( 'userferry_import_xml', __( 'No user records were found in this XML file. Expected a root element with one child element per user.', 'userferry' ) );
			}
			return true;
		}

		/**
		 * Column letters ("AB") to a zero-based index.
		 *
		 * @param string $ref Cell reference such as "AB12".
		 * @return int
		 */
		private static function column_index( $ref ) {
			$letters = preg_replace( '/[^A-Z]/', '', strtoupper( $ref ) );
			$index   = 0;
			$len     = strlen( $letters );
			for ( $i = 0; $i < $len; $i++ ) {
				$index = $index * 26 + ( ord( $letters[ $i ] ) - 64 );
			}
			return $index - 1;
		}

		/**
		 * Concatenated text of all <t> elements below a node (rich text runs).
		 *
		 * @param DOMNode $node Node.
		 * @return string
		 */
		private static function text_of( $node ) {
			$text = '';
			foreach ( $node->getElementsByTagName( 't' ) as $t ) {
				$text .= $t->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
			return $text;
		}

		/**
		 * XLSX reader (first worksheet). No external libraries.
		 *
		 * @param string $source File.
		 * @param array  $state  State.
		 * @return true|WP_Error
		 */
		private static function read_xlsx( $source, array &$state ) {
			if ( ! class_exists( 'ZipArchive' ) || ! class_exists( 'XMLReader' ) ) {
				return new WP_Error( 'userferry_import_xlsx', __( 'Excel import needs the PHP ZipArchive and XMLReader extensions. Save the file as CSV and import that instead.', 'userferry' ) );
			}

			$zip = new ZipArchive();
			if ( true !== $zip->open( $source ) ) {
				return new WP_Error( 'userferry_import_xlsx', __( 'This Excel file could not be opened. Save it again as .xlsx (or CSV) and retry.', 'userferry' ) );
			}

			$limits  = $state['limits'];
			$too_big = new WP_Error( 'userferry_import_xlsx_size', __( 'This Excel file is too large to import safely. Save it as CSV (or split it) and import that instead.', 'userferry' ) );
			$bomb    = new WP_Error( 'userferry_import_xlsx_ratio', __( 'This Excel file expands to an unusually large size and was rejected for safety. Save it again as CSV and import that.', 'userferry' ) );

			// 1) Inspect every entry's uncompressed size and ratio BEFORE reading any of them.
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive property.
			$count = (int) $zip->numFiles;
			if ( $count > $limits['max_xlsx_entries'] ) {
				$zip->close();
				return $too_big;
			}
			$total = 0;
			for ( $i = 0; $i < $count; $i++ ) {
				$entry = $zip->statIndex( $i );
				if ( ! $entry ) {
					continue;
				}
				$total += (int) $entry['size'];
				$ratio  = $entry['comp_size'] > 0 ? $entry['size'] / $entry['comp_size'] : ( $entry['size'] > 0 ? PHP_INT_MAX : 0 );
				if ( $entry['size'] > MB_IN_BYTES && $ratio > $limits['max_xlsx_ratio'] ) {
					$zip->close();
					return $bomb;
				}
			}
			if ( $total > $limits['max_xlsx_total'] ) {
				$zip->close();
				return $too_big;
			}

			// 2) The small metadata parts are only read after their size is known to be small.
			foreach ( array( 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels' ) as $meta ) {
				$entry = $zip->statName( $meta );
				if ( $entry && $entry['size'] > $limits['max_xlsx_meta'] ) {
					$zip->close();
					return $too_big;
				}
			}

			// Locate the first worksheet through the workbook relationships.
			$sheet_path = 'xl/worksheets/sheet1.xml';
			$workbook   = $zip->statName( 'xl/workbook.xml' ) ? $zip->getFromName( 'xl/workbook.xml' ) : false;
			$rels       = $zip->statName( 'xl/_rels/workbook.xml.rels' ) ? $zip->getFromName( 'xl/_rels/workbook.xml.rels' ) : false;
			if ( $workbook && $rels && preg_match( '/<sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $m ) ) {
				if ( preg_match( '/<Relationship\b[^>]*Id="' . preg_quote( $m[1], '/' ) . '"[^>]*Target="([^"]+)"/', $rels, $t )
					|| preg_match( '/<Relationship\b[^>]*Target="([^"]+)"[^>]*Id="' . preg_quote( $m[1], '/' ) . '"/', $rels, $t ) ) {
					$target     = ltrim( $t[1], '/' );
					$sheet_path = 0 === strpos( $target, 'xl/' ) ? $target : 'xl/' . $target;
				}
			}
			unset( $workbook, $rels );

			// 3) Worksheet and shared strings: size limits.
			$sheet = $zip->statName( $sheet_path );
			$sst   = $zip->statName( 'xl/sharedStrings.xml' );
			if ( ! $sheet ) {
				$zip->close();
				return new WP_Error( 'userferry_import_xlsx', __( 'No worksheet was found in this Excel file.', 'userferry' ) );
			}
			if ( $sheet['size'] > $limits['max_xlsx_sheet'] || ( $sst && $sst['size'] > $limits['max_xlsx_strings'] ) ) {
				$zip->close();
				return $too_big;
			}
			$zip->close();

			// Shared strings: streamed, never loaded as one DOM.
			$shared = array();
			if ( $sst ) {
				$strings = new XMLReader();
				if ( $strings->open( 'zip://' . $source . '#xl/sharedStrings.xml', null, LIBXML_NONET ) ) {
					$sdoc   = new DOMDocument();
					$budget = self::memory_budget( $limits['max_xlsx_strings'] );
					$used   = 0;
					while ( @$strings->read() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Malformed XML simply ends the loop.
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						if ( XMLReader::ELEMENT === $strings->nodeType && 'si' === $strings->localName ) {
							$node  = $strings->expand( $sdoc );
							$text  = $node ? self::text_of( $node ) : '';
							$used += strlen( $text ) + 96; // Text plus PHP's per-string and array overhead.
							if ( $used > $budget ) {
								$strings->close();
								return new WP_Error( 'userferry_import_xlsx_memory', __( 'This Excel file contains more text than can be read safely within your server’s memory limit. Save it as CSV and import that instead — CSV is read row by row.', 'userferry' ) );
							}
							$shared[] = $text;
						}
					}
					$strings->close();
				}
			}

			// Worksheet: streamed row by row straight from the zip.
			$reader = new XMLReader();
			if ( ! $reader->open( 'zip://' . $source . '#' . $sheet_path, null, LIBXML_NONET ) ) {
				return new WP_Error( 'userferry_import_xlsx', __( 'The worksheet in this Excel file could not be read.', 'userferry' ) );
			}

			$doc     = new DOMDocument();
			$headers = null;
			while ( @$reader->read() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( XMLReader::ELEMENT !== $reader->nodeType || 'row' !== $reader->localName ) {
					continue;
				}
				$row_node = $reader->expand( $doc );
				if ( ! $row_node ) {
					continue;
				}

				$cells = array();
				$next  = 0;
				foreach ( $row_node->getElementsByTagName( 'c' ) as $cell ) {
					$ref   = $cell->getAttribute( 'r' );
					$index = '' !== $ref ? self::column_index( $ref ) : $next;
					$next  = $index + 1;
					$type  = $cell->getAttribute( 't' );

					if ( 'inlineStr' === $type ) {
						$value = self::text_of( $cell );
					} else {
						$v     = $cell->getElementsByTagName( 'v' )->item( 0 );
						$value = $v ? $v->textContent : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						if ( 's' === $type ) {
							$value = isset( $shared[ (int) $value ] ) ? $shared[ (int) $value ] : '';
						} elseif ( 'b' === $type ) {
							$value = '1' === $value ? '1' : '0';
						}
					}
					if ( $index >= $state['limits']['max_columns'] ) {
						$reader->close();
						return self::too_many_columns( $state['limits'] );
					}
					if ( $index >= 0 ) {
						$cells[ $index ] = (string) $value;
					}
				}

				if ( null === $headers ) {
					if ( empty( array_filter( $cells, 'strlen' ) ) ) {
						continue; // Skip leading blank rows.
					}
					$width = max( array_keys( $cells ) ) + 1;
					if ( $width > $state['limits']['max_columns'] ) {
						$reader->close();
						return self::too_many_columns( $state['limits'] );
					}
					$raw = array();
					for ( $i = 0; $i < $width; $i++ ) {
						$raw[] = isset( $cells[ $i ] ) ? $cells[ $i ] : '';
					}
					$headers = self::normalise_headers( $raw );
					continue;
				}

				foreach ( $cells as $i => $cell_value ) {
					if ( $i >= count( $headers ) && '' !== trim( $cell_value ) ) {
						$reader->close();
						return self::unnamed_values( $state['total'] + 2 );
					}
				}
				$row = array();
				foreach ( $headers as $i => $header ) {
					$row[ $header ] = isset( $cells[ $i ] ) ? $cells[ $i ] : '';
				}
				if ( ! self::emit( $row, $state ) ) {
					break;
				}
			}
			$reader->close();

			if ( null !== $headers && empty( $state['headers'] ) ) {
				$state['headers'] = $headers;
			}
			return true;
		}
	}
}
