<?php
/**
 * Tiny hook registry so class-plugin.php can wire up actions/filters
 * declaratively instead of scattering add_action() calls everywhere.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Loader' ) ) {

	/**
	 * Class UserFerry_Loader
	 */
	class UserFerry_Loader {

		/**
		 * Queued actions.
		 *
		 * @var array
		 */
		protected $actions = array();

		/**
		 * Queued filters.
		 *
		 * @var array
		 */
		protected $filters = array();

		/**
		 * Queue an action.
		 *
		 * @param string        $hook          Hook name.
		 * @param object|string $component    Object instance or class name.
		 * @param string        $callback      Method name.
		 * @param int           $priority      Priority.
		 * @param int           $accepted_args Accepted argument count.
		 */
		public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Queue a filter.
		 *
		 * @param string        $hook          Hook name.
		 * @param object|string $component    Object instance or class name.
		 * @param string        $callback      Method name.
		 * @param int           $priority      Priority.
		 * @param int           $accepted_args Accepted argument count.
		 */
		public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Shared push logic.
		 *
		 * @param array         $collection    Existing collection.
		 * @param string        $hook          Hook name.
		 * @param object|string $component    Object instance or class name.
		 * @param string        $callback      Method name.
		 * @param int           $priority      Priority.
		 * @param int           $accepted_args Accepted argument count.
		 * @return array
		 */
		private function add( $collection, $hook, $component, $callback, $priority, $accepted_args ) {
			$collection[] = array(
				'hook'          => $hook,
				'component'     => $component,
				'callback'      => $callback,
				'priority'      => $priority,
				'accepted_args' => $accepted_args,
			);
			return $collection;
		}

		/**
		 * Register everything queued with WordPress.
		 */
		public function run() {
			foreach ( $this->filters as $hook ) {
				add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
			foreach ( $this->actions as $hook ) {
				add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}
	}
}
