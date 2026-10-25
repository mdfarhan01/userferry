<?php
/**
 * Export templates storage.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table owned by this plugin; values are prepared.

if ( ! class_exists( 'UserFerry_Templates' ) ) {

	/**
	 * CRUD for saved export templates.
	 */
	class UserFerry_Templates {

		/**
		 * All templates, newest first, as arrays.
		 *
		 * @return array[]
		 */
		public static function all() {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY updated_at DESC, id DESC', UserFerry_Install::templates_table() ) );
			return array_map( array( __CLASS__, 'to_array' ), (array) $rows );
		}

		/**
		 * Count templates.
		 *
		 * @return int
		 */
		public static function count() {
			global $wpdb;
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', UserFerry_Install::templates_table() ) );
		}

		/**
		 * Get one template.
		 *
		 * @param int $id Template id.
		 * @return array|null
		 */
		public static function get( $id ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', UserFerry_Install::templates_table(), absint( $id ) ) );
			return $row ? self::to_array( $row ) : null;
		}

		/**
		 * Normalise a DB row.
		 *
		 * @param object $row Row.
		 * @return array
		 */
		public static function to_array( $row ) {
			$filters = UserFerry_Export_Config::sanitize_filters( UserFerry_Export_Config::decode( $row->filters ) );
			$fields  = array_values( array_filter( array_map( 'strval', UserFerry_Export_Config::decode( $row->fields ) ) ) );
			return array(
				'id'          => (int) $row->id,
				'name'        => (string) $row->name,
				'description' => (string) $row->description,
				'fields'      => $fields,
				'filters'     => $filters,
				'format'      => UserFerry_Export_Config::sanitize_format( $row->format ),
				'filename'    => (string) $row->filename,
				'updated_at'  => (string) $row->updated_at,
				'summary'     => UserFerry_Export_Config::describe_filters( $filters ),
			);
		}

		/**
		 * Validate input coming from the UI.
		 *
		 * @param array $raw Raw (already unslashed) data.
		 * @return array|\WP_Error
		 */
		public static function prepare( array $raw ) {
			$name = isset( $raw['name'] ) ? trim( sanitize_text_field( $raw['name'] ) ) : '';
			if ( '' === $name ) {
				return new \WP_Error( 'userferry_template_name', __( 'Give the template a name.', 'userferry' ) );
			}

			$fields = UserFerry_Export_Config::sanitize_fields( UserFerry_Export_Config::decode( isset( $raw['fields'] ) ? $raw['fields'] : array() ) );
			if ( empty( $fields ) ) {
				return new \WP_Error( 'userferry_template_fields', __( 'Choose at least one field for this template.', 'userferry' ) );
			}

			$filters = UserFerry_Export_Config::sanitize_filters( UserFerry_Export_Config::decode( isset( $raw['filters'] ) ? $raw['filters'] : array() ) );
			if ( 'role' === $filters['scope'] && empty( $filters['roles'] ) ) {
				return new \WP_Error( 'userferry_template_roles', __( 'Choose at least one role, or include all users.', 'userferry' ) );
			}

			return array(
				'id'          => isset( $raw['id'] ) ? absint( $raw['id'] ) : 0,
				'name'        => substr( $name, 0, 191 ),
				'description' => isset( $raw['description'] ) ? sanitize_textarea_field( $raw['description'] ) : '',
				'fields'      => $fields,
				'filters'     => $filters,
				'format'      => UserFerry_Export_Config::sanitize_format( isset( $raw['format'] ) ? $raw['format'] : 'csv' ),
				'filename'    => UserFerry_Export_Config::sanitize_filename( isset( $raw['filename'] ) ? $raw['filename'] : '' ),
			);
		}

		/**
		 * Insert or update.
		 *
		 * @param array $data Prepared data.
		 * @return int|\WP_Error Template id.
		 */
		public static function save( array $data ) {
			global $wpdb;
			$table = UserFerry_Install::templates_table();
			$now   = current_time( 'mysql' );

			$row = array(
				'name'        => $data['name'],
				'description' => $data['description'],
				'fields'      => wp_json_encode( array_values( $data['fields'] ) ),
				'filters'     => wp_json_encode( $data['filters'] ),
				'format'      => $data['format'],
				'filename'    => $data['filename'],
				'updated_at'  => $now,
			);

			if ( ! empty( $data['id'] ) ) {
				if ( ! self::get( $data['id'] ) ) {
					return new \WP_Error( 'userferry_template_missing', __( 'That template no longer exists.', 'userferry' ) );
				}
				$wpdb->update( $table, $row, array( 'id' => (int) $data['id'] ) );
				return (int) $data['id'];
			}

			$row['created_at'] = $now;
			$row['created_by'] = get_current_user_id();
			if ( false === $wpdb->insert( $table, $row ) ) {
				return new \WP_Error( 'userferry_template_db', __( 'The template could not be saved. Deactivate and reactivate the plugin to repair its database tables.', 'userferry' ) );
			}
			return (int) $wpdb->insert_id;
		}

		/**
		 * Duplicate a template.
		 *
		 * @param int $id Template id.
		 * @return int|\WP_Error New id.
		 */
		public static function duplicate( $id ) {
			$template = self::get( $id );
			if ( ! $template ) {
				return new \WP_Error( 'userferry_template_missing', __( 'That template no longer exists.', 'userferry' ) );
			}
			$template['id'] = 0;
			/* translators: %s: template name */
			$template['name'] = sprintf( __( '%s (copy)', 'userferry' ), $template['name'] );
			return self::save( $template );
		}

		/**
		 * Delete a template.
		 *
		 * @param int $id Template id.
		 * @return bool
		 */
		public static function delete( $id ) {
			global $wpdb;
			return (bool) $wpdb->delete( UserFerry_Install::templates_table(), array( 'id' => absint( $id ) ) );
		}

		/**
		 * Export config for a template (shape used by UserFerry_Export_Job::start).
		 *
		 * @param array $template Template.
		 * @return array
		 */
		public static function to_config( array $template ) {
			return array(
				'filters'       => $template['filters'],
				'fields'        => UserFerry_Export_Config::sanitize_fields( $template['fields'] ),
				'format'        => $template['format'],
				'filename'      => $template['filename'],
				'template_id'   => $template['id'],
				'template_name' => $template['name'],
			);
		}
	}
}
