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
	protected $id;
	protected $key;
	protected $time;

	/**
	 * Takes two optional arguments: API key and timestamp
	 *
	 * API Key argument is inteded to be filtered using the 'kissmetrics_key' filter
	 * Optional time argument defaults to current time
	 *
	 * This is required before doing anything else
	 */
	function init( $key = null, $time = null ) {
		if ( ! apply_filters( 'kissmetrics_init', true ) )
			return $this->is_initialized();

		$this->key  = apply_filters( 'kissmetrics_key', $key );
		$this->time = ( is_int( $time ) ) ? $time : time();

		return $this->is_initialized();
	}

	/**
	 * Identify the user we're going to record data about
	 * This is required before recording any data
	 *
	 * identify( get_current_user_id() );
	 */
	function identify( $id ) {
		if ( ! $id )
			return $this->is_identified();

		$this->id = apply_filters( 'kissmetrics_identify', $id );

		/* Use the Kissmetrics auto-generated ID cookie to alias our logged in ID, then delete the cookie */
		if ( isset( $_COOKIE['km_ai'] ) && ! headers_sent() && apply_filters( 'kissmetrics_auto_alias', true ) ) {
			$this->alias( $_COOKIE['km_ai'], $this->id );
			setcookie( 'km_ai', '', time() - 3600 );
		}

		return $this->is_identified();
	}

	/**
	 * Record data about an identified user
	 * Accepts a required argument 'event' as a string
	 * Accepts an optional argument 'properties' as an array
	 *
	 * Example: record( 'Viewed Homepage', array( 'campaign' => 'Adwords') );
	 */
	function record( $event, $properties = array() ) {
		if ( ! $this->is_initialized_and_identified() )
			return false;

		$args = array_merge( $properties, array(
			'_n' => $event,
		) );
		$args = (array) apply_filters( 'kissmetrics_record', $args );

		return $this->generate_query( 'e', $args );
	}

	/**
	 * Set properties about an identified user
	 * Accepts a single argument 'properties' as an array
	 *
	 * Example: set( array( 'language' => 'en', 'favorite_bbq' => 'brisket' ) );
	 */
	function set( $properties = array() ) {
		if ( ! $this->is_initialized_and_identified() )
			return false;

		$args = (array) apply_filters( 'kissmetrics_set', $args );

		return $this->generate_query( 's', $properties );
	}

	/**
	 * Alias a new user identiy to a new one in Kissmetrics' data
	 * Possible use case would be assigning logged out user an identifier and aliasing their User ID after signup
	 *
	 * Example: alias( get_current_user_id(), $_COOKIE['tmp_km_thing'] );
	 */
	function alias( $name, $alias_to ) {
		if ( ! $this->is_initialized() )
			return false;

		$args = array(
			'_p' => $name,
			'_n' => $alias_to,
		);
		$args = (array) apply_filters( 'kissmetrics_alias', $args );

		return $this->generate_query( 'a', $args, false );
	}

	/**
	 * Undo any initialization or identification
	 */
	function reset() {
		$this->id  = null;
		$this->key = null;

		return ! $this->id && ! $this->key;
	}

	/* Protected */

	/* Check to make sure both the API key and User ID are set */
	protected function is_initialized_and_identified() {
		return $this->is_initialized() && $this->is_identified();
	}

	protected function is_initialized() {
		return (bool) $this->key;
	}

	protected function is_identified() {
		return (bool) $this->id;
	}

	/* Put together the query we're going to send to Kissmetrics */
	protected function generate_query( $type, $data, $update = true ) {
		// Setup query params
		if( $update )
			$data['_p'] = $this->id;   // User ID

		$data['_k']   = $this->key;  // API key
		$data['_t']   = $this->time; // Timestamp
		$data['_d']   = 1;           // Force Kissmetrics to use our timestamp

		$data         = apply_filters( 'kissmetrics_generate_query_args', $data );

		$query = '/' . $type . '?' . http_build_query( $data, '', '&', PHP_QUERY_RFC3986 );
		$query = apply_filters( 'kissmetrics_generate_query_string', $query );

		return $this->send_query( $query );
	}

	/* Actually send Kissmetrics some data */
	protected function send_query( $query ) {
		$host = apply_filters( 'kissmetrics_host', 'http://trk.kissmetrics.com:80' );
		$endpoint = $host . $query;

		$args = array( 'blocking' => false );
		$args = apply_filters( 'kissmetrics_send_query_args', $args );

		return wp_remote_get( $endpoint, $args );
	}
}

$wp_kissmetrics = new WP_Kissmetrics;
require_once( dirname( __FILE__ ) . '/helpers.php' );