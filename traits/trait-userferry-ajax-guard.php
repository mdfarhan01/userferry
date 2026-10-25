<?php
/**
 * Shared AJAX helpers for every module's AJAX controller: capability +
 * nonce checks, raw input reading and error responses.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'UserFerry_Ajax_Guard' ) ) {

	/**
	 * Trait UserFerry_Ajax_Guard
	 */
	trait UserFerry_Ajax_Guard {

		/**
		 * Verify capability (or capabilities) and the "userferry_nonce" nonce.
		 *
		 * @param string|string[] $capabilities Required capabilities; defaults to the plugin capability.
		 */
		protected function guard( $capabilities = '' ) {
			$capabilities = $capabilities ? (array) $capabilities : array( userferry_capability() );
			foreach ( $capabilities as $capability ) {
				if ( ! current_user_can( $capability ) ) {
					wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'userferry' ) ), 403 );
				}
			}
			if ( ! check_ajax_referer( 'userferry_nonce', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page and try again.', 'userferry' ) ), 403 );
			}
		}

		/*
		 * Request accessors.
		 *
		 * Every accessor verifies the request nonce itself, right before it reads
		 * $_POST, and returns sanitized data only. (guard() has already verified
		 * the nonce and the user's capability; checking again here keeps each
		 * read of request data self-evidently safe.)
		 */

		/**
		 * Sanitized single-line text.
		 *
		 * @param string $key      Key.
		 * @param string $fallback Default.
		 * @return string
		 */
		protected function post_text( $key, $fallback = '' ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				return $fallback;
			}
			return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $fallback;
		}

		/**
		 * Sanitized multi-line text.
		 *
		 * @param string $key Key.
		 * @return string
		 */
		protected function post_textarea( $key ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				return '';
			}
			return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
		}

		/**
		 * Non-negative integer.
		 *
		 * @param string $key      Key.
		 * @param int    $fallback Default.
		 * @return int
		 */
		protected function post_int( $key, $fallback = 0 ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				return (int) $fallback;
			}
			return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : (int) $fallback;
		}

		/**
		 * Key (lowercase letters, numbers, dashes, underscores).
		 *
		 * @param string $key Key.
		 * @return string
		 */
		protected function post_key( $key ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				return '';
			}
			return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : '';
		}

		/**
		 * Array (any depth) with every value sanitized as text.
		 *
		 * @param string $key Key.
		 * @return array
		 */
		protected function post_array( $key ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				return array();
			}
			return isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? map_deep( wp_unslash( $_POST[ $key ] ), 'sanitize_text_field' ) : array();
		}

		/**
		 * List sent as one value per line (keeps order, no max_input_vars issues).
		 *
		 * @param string $key Key.
		 * @return string[]
		 */
		protected function post_lines( $key ) {
			$text = $this->post_textarea( $key );
			return '' === $text ? array() : array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ), 'strlen' ) );
		}

		/**
		 * Send a WP_Error as a JSON error response.
		 *
		 * @param WP_Error $error Error.
		 */
		protected function fail( WP_Error $error ) {
			wp_send_json_error( array( 'message' => $error->get_error_message() ) );
		}

		/**
		 * Send success, or the error if $result is a WP_Error.
		 *
		 * @param mixed $result Result.
		 */
		protected function respond( $result ) {
			if ( is_wp_error( $result ) ) {
				$this->fail( $result );
			}
			wp_send_json_success( $result );
		}
	}
}
