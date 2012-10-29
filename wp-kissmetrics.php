<?php
/*
Plugin Name: Kissmetrics For Developers
Description: A generic class and helper functions usable for recording all kinds of data with Kissmetrics and customized to use WordPress API's.
Version: 1.0
Author: Evan Solomon
Author URI: http://evansolomon.me
License: GPL v2 or later
*/

/* Based on https://github.com/kissmetrics/KISSmetrics/blob/master/km.php */

class WP_Kissmetrics {
	// Query requirements
	static $id                 = null;
	static $key                = null;
	static $time               = null;

	// Batched queries
	static $queued_queries     = array();
	static $js_queries         = array();

	// Human-friendly query type names
	static $query_type_mapping = array(
		'record' => 'e', // Record events
		'set'    => 's', // Set properties
		'alias'  => 'a', // Alias user Id's
	);

	/**
	 * Setup Kissmetrics API key and timestamp for the data to be recorded
	 * Uses 'kissmetrics_api_key' fitler
	 *
	 * This is required before making any queries
	 */
	static function init( $key = null, $time = null ) {
		// Make sure Kissmetrics has not been disabled
		if ( ! self::is_enabled() )
			return;

		self::$key  = apply_filters( 'kissmetrics_api_key', $key );
		self::$time = ( is_int( $time ) ) ? $time : time();
	}

	/**
	 * Identify the user we to record data about
	 * This is required before recording events or properties
	 *
	 * On WordPress.com we use usernames for identifiers
	 * If you pass an email or user_id it will get automatically converted to a username
	 */
	static function identify( $id ) {
		if ( ! $id )
			return;
		elseif ( is_int( $id ) )
			$id = get_user_by( 'id', $id )->user_login;
		elseif ( is_email( $id ) )
			$id = get_user_by( 'email', $id )->user_login;

		self::$id = $id;
	}


	/**
	 * Centralize some common tasks in helper functions
	 */
	static function init_and_identify( $api_key, $identity ) {
		if ( ! $api_key )
			$api_key = self::get_default_api_key();

		self::init( $api_key );
		self::identify( $identity );
	}

	/**
	 * Record an event for the identified user
	 *
	 * $action Unique name for the event being recorded
	 * $properties Metadata about the event being recorded, default empty
	 * $prefix_properties Whether or not to prefix property names with event name,
	 *   default true to avoid conflicting property names
	 */
	static function record( $action, $props = array(), $prefix_properties = true ) {
		if ( ! self::is_initialized_and_identified() )
			return;

		if ( $prefix_properties )
			$props = self::prefix_properties( $props, $action );

		// _n is the Kissmetrics API property for event name
		$data = array_merge( $props, array( '_n' => $action ) );

		self::generate_query( self::$query_type_mapping['record'], $data );
	}

	/**
	 * Set properties about the identified user
	 *
	 * $properties Array of properties and values with named indices
	 */
	static function set( $properties = array() ) {
		if ( ! self::is_initialized_and_identified() )
			return;

		if ( ! $properties || ! is_array( $properties ) )
			return;

		// Arrays should not be 0-indexed because indices are used as property names
		if ( array_key_exists( 0, $properties ) )
			return;

		self::generate_query( self::$query_type_mapping['set'], $properties );
	}

	/**
	 * Alias a new user identiy to another, combines both users' data in Kissmetrics
	 *
	 * Argument order does not matter
	 */
	static function alias( $name, $alias_to ) {
		if ( $name == $alias_to )
			return;

		if ( ! self::is_initialized() )
			return;

		$array = array(
			'_p' => $name,
			'_n' => $alias_to,
		);

		self::generate_query( self::$query_type_mapping['alias'], $array, false );
	}

	/**
	 * Register JS API
	 */
	static function register_js() {
		wp_register_script( 'kissmetrics', plugins_url( '/kissmetrics.js', __FILE__ ), array( 'jquery' ), '20121028', true );
	}

	/**
	 * Enqueue JavaScript API
	 */
	static function enqueue_js() {
		if ( ! self::is_enabled() )
			return;

		self::register_js();
		wp_enqueue_script( 'kissmetrics' );
		self::setup_js_api();
	}

	/**
	 * Pass API key and username to Kissmetrics
	 */
	static protected function setup_js_api() {
		static $api_setup;

		// Only do this once per page load
		if ( $api_setup )
			return;

		$api_setup = array();
		$api_setup['api_key'] = self::get_default_api_key();

		if ( is_user_logged_in() )
			$api_setup['username'] = get_user_by( 'id', get_current_user_id() )->user_login;

		wp_localize_script( 'kissmetrics', 'kissmetrics_api', $api_setup );
	}

	/**
	 * Pass an event and optional properties to be recorded client side
	 */
	static function record_js_event( $event, $properties = array() ) {
		self::$js_queries['events'][] = array( 'name' => $event, 'properties' => $properties );
		self::setup_js_queries();
	}

	/**
	 * Pass a single property to be recorded client side
	 */
	static function set_js_property( $property ) {
		self::$js_queries['properties'][] = $property;
		self::setup_js_queries();
	}

	/**
	 * Add actions to print JS data
	 */
	static protected function setup_js_queries() {
		// Early priority to get in before the scripts are printed
		add_action( 'wp_print_footer_scripts',    array( __CLASS__, 'print_js_queries' ), 9 );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_js_queries' ), 9 );
	}

	/**
	 * Print the data to be used by kissmetrics.js
	 * Assumes kissmetrics.js is enqueued in the footer, which it is by default
	 */
	static function print_js_queries() {
		wp_localize_script( 'kissmetrics', 'kissmetrics_queries', self::$js_queries );
	}

	/**
	 * Used to avoid conflicting like-named properties of different events
	 */
	static function prefix_properties( $props = array(), $prefix = '' ) {
		foreach ( $props as $key => $value ) {
			unset( $props[ $key ] );
			$props["{$prefix} | {$key}"] = $value;
		}

		return $props;
	}

	/**
	 * Centralize dealing with the constant in a single place
	 */
	static protected function get_default_api_key() {
		$api_key = ( defined( 'WPCOM_KISSMETRICS_API_KEY' ) ) ? WPCOM_KISSMETRICS_API_KEY : null;

		return apply_filters( 'kissmetrics_api_key', $api_key );
	}

	/**
	 * Allow filters to turn Kissmetrics on or off
	 */
	static protected function is_enabled() {
		return apply_filters( 'kissmetrics_is_enabled', true );
	}

	/**
	 * Clear API key and identity
	 * Runs automatically after each query
	 */
	static protected function reset() {
		self::$id  = null;
		self::$key = null;
	}

	/**
	 * Check to make sure both the API key and User ID are set, boolean
	 */
	static protected function is_initialized_and_identified() {
		return self::is_initialized() && self::$id;
	}

	/**
	 * Check that an API key is set, boolean
	 */
	static protected function is_initialized() {
		return (bool) self::$key;
	}

	/**
	 * Create the query string we're going to use to request the Kissmetrics API
	 */
	static protected function generate_query( $type, $data ) {

		$data['_k'] = self::$key;  // API key
		$data['_t'] = self::$time; // Timestamp
		$data['_d'] = 1;           // Force Kissmetrics to use the time value we pass
		$data['_p'] = self::$id;   // User identity

		$query = '/' . $type . '?' . http_build_query( $data, '', '&' );

		// Encode spaces as %20 instead of +
		// PHP 5.4 supports a fourth argument (enc_type) to do this more gracefully
		// See: http://php.net/manual/en/function.http-build-query.php
		$query = str_replace( '+', '%20', $query );

		self::queue_query( $query );
		self::reset();
		do_action( 'kissmetrics_generate_query', array_search( $type, self::$query_type_mapping ), $data );
	}

	/**
	 * Add the query to a queue and set the queue to be processed on shutdown
	 */
	static protected function queue_query( $query ) {
		self::$queued_queries[] = $query;
		add_action( 'shutdown', array( __CLASS__, 'send_queued_queries' ) );
	}

	/**
	 * Process the queued queries
	 */
	static function send_queued_queries() {
		foreach ( (array) self::$queued_queries as $query )
			self::send_query( $query );

		// Kill the queue after its processed in case this gets called multiple times
		self::$queued_queries = array();
	}

	/**
	 * Make an HTTP request to the Kissmetrics API
	 */
	static protected function send_query( $query ) {
		$request_url = 'http://trk.kissmetrics.com:80' . $query;
		wp_remote_get( $request_url, array(
			'timeout'  => 1,
		) );
	}
}
