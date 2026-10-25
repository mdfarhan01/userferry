<?php
/**
 * File writers.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions -- Streaming large exports row by row requires native file handles; all paths are inside the plugin's own uploads sub-folder.

if ( ! class_exists( 'UserFerry_Export_Writer' ) ) {

	/**
	 * Converts a job's temporary JSON-lines file into CSV, XLSX, JSON or XML
	 * by streaming one row at a time (low memory use, no external libraries).
	 */
	class UserFerry_Export_Writer {

		/**
		 * Supported formats.
		 *
		 * @return array format => [ label, mime ]
		 */
		public static function formats() {
			return array(
				'csv'  => array(
					'label' => 'CSV',
					'mime'  => 'text/csv',
				),
				'xlsx' => array(
					'label' => 'Excel (XLSX)',
					'mime'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				),
				'json' => array(
					'label' => 'JSON',
					'mime'  => 'application/json',
				),
				'xml'  => array(
					'label' => 'XML',
					'mime'  => 'application/xml',
				),
			);
		}

		/**
		 * Write the final file.
		 *
		 * @param string $format  Format key.
		 * @param string $part    Temporary JSON-lines file.
		 * @param string $target  Absolute path without extension.
		 * @param array  $headers key => label, in column order.
		 * @return string|\WP_Error Absolute path of the written file.
		 */
		public static function write( $format, $part, $target, array $headers ) {
			$file = $target . '.' . $format;

			switch ( $format ) {
				case 'xlsx':
					$ok = self::xlsx( $part, $file, $headers );
					break;
				case 'json':
					$ok = self::json( $part, $file, $headers );
					break;
				case 'xml':
					$ok = self::xml( $part, $file, $headers );
					break;
				default:
					$ok = self::csv( $part, $file, $headers );
			}

			if ( true !== $ok ) {
				if ( file_exists( $file ) ) {
					wp_delete_file( $file );
				}
				return is_wp_error( $ok ) ? $ok : new \WP_Error( 'userferry_write_failed', __( 'The export file could not be written.', 'userferry' ) );
			}

			return $file;
		}

		/**
		 * Iterate the rows of a JSON-lines file.
		 *
		 * @param string   $part     File.
		 * @param callable $callback Receives each row (array).
		 * @return bool
		 */
		private static function each_row( $part, callable $callback ) {
			$handle = fopen( $part, 'rb' );
			if ( ! $handle ) {
				return false;
			}
			while ( true ) {
				$line = fgets( $handle );
				if ( false === $line ) {
					break;
				}
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				$row = json_decode( $line, true );
				if ( is_array( $row ) ) {
					$callback( $row );
				}
			}
			fclose( $handle );
			return true;
		}

		/**
		 * Neutralise spreadsheet formulas (CSV/formula injection).
		 *
		 * @param string $value Value.
		 * @return string
		 */
		private static function protect( $value ) {
			static $enabled = null;
			if ( null === $enabled ) {
				$enabled = (bool) UserFerry_Settings::get( 'formula_protection' );
			}
			$value = (string) $value;
			if ( '' !== $value && $enabled && false !== strpos( "=+-@\t\r", $value[0] ) && ! is_numeric( $value ) ) {
				return "'" . $value;
			}
			return $value;
		}

		/**
		 * CSV writer.
		 *
		 * @param string $part    Source.
		 * @param string $file    Target.
		 * @param array  $headers Headers.
		 * @return bool
		 */
		private static function csv( $part, $file, array $headers ) {
			$out = fopen( $file, 'wb' );
			if ( ! $out ) {
				return false;
			}

			$delimiters = array(
				'comma'     => ',',
				'semicolon' => ';',
				'tab'       => "\t",
			);
			$setting    = UserFerry_Settings::get( 'csv_delimiter' );
			$delimiter  = isset( $delimiters[ $setting ] ) ? $delimiters[ $setting ] : ',';

			if ( UserFerry_Settings::get( 'csv_bom' ) ) {
				fwrite( $out, "\xEF\xBB\xBF" );
			}

			fputcsv( $out, array_map( array( __CLASS__, 'protect' ), array_values( $headers ) ), $delimiter, '"', '\\' );

			$keys = array_keys( $headers );
			$ok   = self::each_row(
				$part,
				static function ( $row ) use ( $out, $keys, $delimiter ) {
					$line = array();
					foreach ( $keys as $key ) {
						$line[] = self::protect( isset( $row[ $key ] ) ? $row[ $key ] : '' );
					}
					fputcsv( $out, $line, $delimiter, '"', '\\' );
				}
			);

			fclose( $out );
			return $ok;
		}

		/**
		 * JSON writer (array of objects keyed by field key).
		 *
		 * @param string $part    Source.
		 * @param string $file    Target.
		 * @param array  $headers Headers.
		 * @return bool
		 */
		private static function json( $part, $file, array $headers ) {
			$out = fopen( $file, 'wb' );
			if ( ! $out ) {
				return false;
			}

			$keys  = array_keys( $headers );
			$first = true;
			fwrite( $out, "[\n" );

			$ok = self::each_row(
				$part,
				static function ( $row ) use ( $out, $keys, &$first ) {
					$item = array();
					foreach ( $keys as $key ) {
						$item[ $key ] = isset( $row[ $key ] ) ? $row[ $key ] : '';
					}
					fwrite( $out, ( $first ? '' : ",\n" ) . '  ' . wp_json_encode( $item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
					$first = false;
				}
			);

			fwrite( $out, "\n]\n" );
			fclose( $out );
			return $ok;
		}

		/**
		 * Turn a field key into a valid XML element name.
		 *
		 * @param string $key Key.
		 * @return string
		 */
		private static function xml_tag( $key ) {
			$tag = preg_replace( '/[^A-Za-z0-9_.-]/', '_', (string) $key );
			if ( '' === $tag || ! preg_match( '/^[A-Za-z_]/', $tag ) || 0 === stripos( $tag, 'xml' ) ) {
				$tag = 'field_' . $tag;
			}
			return $tag;
		}

		/**
		 * XML writer.
		 *
		 * @param string $part    Source.
		 * @param string $file    Target.
		 * @param array  $headers Headers.
		 * @return bool|\WP_Error
		 */
		private static function xml( $part, $file, array $headers ) {
			if ( ! class_exists( 'XMLWriter' ) ) {
				return new \WP_Error( 'userferry_no_xmlwriter', __( 'XML export needs the PHP XMLWriter extension.', 'userferry' ) );
			}

			$xml = new \XMLWriter();
			if ( ! $xml->openUri( $file ) ) {
				return false;
			}
			$xml->setIndent( true );
			$xml->setIndentString( '  ' );
			$xml->startDocument( '1.0', 'UTF-8' );
			$xml->startElement( 'users' );

			$tags = array();
			foreach ( array_keys( $headers ) as $key ) {
				$tags[ $key ] = self::xml_tag( $key );
			}

			$count = 0;
			$ok    = self::each_row(
				$part,
				static function ( $row ) use ( $xml, $tags, &$count ) {
					$xml->startElement( 'user' );
					foreach ( $tags as $key => $tag ) {
						$xml->writeElement( $tag, self::clean_xml_text( isset( $row[ $key ] ) ? $row[ $key ] : '' ) );
					}
					$xml->endElement();
					++$count;
					if ( 0 === $count % 200 ) {
						$xml->flush();
					}
				}
			);

			$xml->endElement();
			$xml->endDocument();
			$xml->flush();
			return $ok;
		}

		/**
		 * Remove characters that are invalid in XML 1.0.
		 *
		 * @param string $value Value.
		 * @return string
		 */
		private static function clean_xml_text( $value ) {
			$value = wp_check_invalid_utf8( (string) $value, true );
			return (string) preg_replace( '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value );
		}

		/**
		 * Escape a value for an XLSX cell.
		 *
		 * @param string $value Value.
		 * @return string
		 */
		private static function xlsx_text( $value ) {
			$value = self::clean_xml_text( $value );
			if ( function_exists( 'mb_substr' ) && mb_strlen( $value ) > 32767 ) {
				$value = mb_substr( $value, 0, 32767 );
			}
			return htmlspecialchars( $value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8' );
		}

		/**
		 * 0 -> A, 25 -> Z, 26 -> AA.
		 *
		 * @param int $index Zero based index.
		 * @return string
		 */
		public static function column_letter( $index ) {
			$letters = '';
			$index   = (int) $index + 1;
			while ( $index > 0 ) {
				$mod     = ( $index - 1 ) % 26;
				$letters = chr( 65 + $mod ) . $letters;
				$index   = (int) ( ( $index - $mod ) / 26 );
			}
			return $letters;
		}

		/**
		 * XLSX writer: streams the worksheet to a temp file, then zips the package.
		 *
		 * @param string $part    Source.
		 * @param string $file    Target.
		 * @param array  $headers Headers.
		 * @return bool|\WP_Error
		 */
		private static function xlsx( $part, $file, array $headers ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return new \WP_Error( 'userferry_no_zip', __( 'Excel export needs the PHP ZipArchive extension.', 'userferry' ) );
			}

			$sheet_file = $file . '.sheet.tmp';
			$sheet      = fopen( $sheet_file, 'wb' );
			if ( ! $sheet ) {
				return false;
			}

			$keys    = array_keys( $headers );
			$columns = array();
			foreach ( $keys as $i => $key ) {
				$columns[ $key ] = self::column_letter( $i );
			}
			$last_col = self::column_letter( max( 0, count( $keys ) - 1 ) );

			fwrite( $sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" );
			fwrite( $sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' );
			fwrite( $sheet, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' );

			fwrite( $sheet, '<cols>' );
			foreach ( array_values( $headers ) as $i => $label ) {
				$width = min( 45, max( 12, (int) ( strlen( $label ) * 1.3 ) + 4 ) );
				fwrite( $sheet, '<col min="' . ( $i + 1 ) . '" max="' . ( $i + 1 ) . '" width="' . $width . '" customWidth="1"/>' );
			}
			fwrite( $sheet, '</cols><sheetData>' );

			$header_xml = '<row r="1">';
			foreach ( $keys as $key ) {
				$header_xml .= '<c r="' . $columns[ $key ] . '1" t="inlineStr" s="1"><is><t>' . self::xlsx_text( $headers[ $key ] ) . '</t></is></c>';
			}
			fwrite( $sheet, $header_xml . '</row>' );

			$row_num = 1;
			$ok      = self::each_row(
				$part,
				static function ( $row ) use ( $sheet, $keys, $columns, &$row_num ) {
					++$row_num;
					$xml = '<row r="' . $row_num . '">';
					foreach ( $keys as $key ) {
						$value = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
						$ref   = $columns[ $key ] . $row_num;
						if ( '' === $value ) {
							continue;
						}
						if ( preg_match( '/^(0|-?[1-9]\d{0,14})$/', $value ) ) {
							$xml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
						} else {
							$xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . self::xlsx_text( $value ) . '</t></is></c>';
						}
					}
					fwrite( $sheet, $xml . '</row>' );
				}
			);

			fwrite( $sheet, '</sheetData>' );
			fwrite( $sheet, '<autoFilter ref="A1:' . $last_col . max( 1, $row_num ) . '"/>' );
			fwrite( $sheet, '</worksheet>' );
			fclose( $sheet );

			if ( ! $ok ) {
				wp_delete_file( $sheet_file );
				return false;
			}

			$zip = new \ZipArchive();
			if ( true !== $zip->open( $file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
				wp_delete_file( $sheet_file );
				return false;
			}

			$now = gmdate( 'Y-m-d\TH:i:s\Z' );

			$zip->addFromString(
				'[Content_Types].xml',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
				'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
				'<Default Extension="xml" ContentType="application/xml"/>' .
				'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
				'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
				'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
				'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' .
				'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' .
				'</Types>'
			);

			$zip->addFromString(
				'_rels/.rels',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
				'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
				'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>' .
				'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' .
				'</Relationships>'
			);

			$zip->addFromString(
				'docProps/core.xml',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
				'<dc:title>Users export</dc:title><dc:creator>UserFerry</dc:creator>' .
				'<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>' .
				'</cp:coreProperties>'
			);

			$zip->addFromString(
				'docProps/app.xml',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>UserFerry</Application></Properties>'
			);

			$zip->addFromString(
				'xl/workbook.xml',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
				'<sheets><sheet name="Users" sheetId="1" r:id="rId1"/></sheets>' .
				'<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">Users!$A$1:$' . $last_col . '$' . max( 1, $row_num ) . '</definedName></definedNames>' .
				'</workbook>'
			);

			$zip->addFromString(
				'xl/_rels/workbook.xml.rels',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
				'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
				'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
				'</Relationships>'
			);

			$zip->addFromString(
				'xl/styles.xml',
				'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
				'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
				'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>' .
				'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2E3192"/><bgColor indexed="64"/></patternFill></fill></fills>' .
				'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' .
				'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
				'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>' .
				'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' .
				'</styleSheet>'
			);

			$zip->addFile( $sheet_file, 'xl/worksheets/sheet1.xml' );
			$closed = $zip->close();
			wp_delete_file( $sheet_file );

			return (bool) $closed;
		}
	}
}
