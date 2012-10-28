<?php

/* Just a couple of simple helper functions to record data */

/**
 * Helper function: Record events
 */
function kissmetrics_record_event( $identity, $event, $properties = array(), $api_key = null ) {
	WP_Kissmetrics::init_and_identify( $api_key, $identity );
	WP_Kissmetrics::record( $event, $properties );
}

/**
 * Helper function: Set properties
 */
function kissmetrics_set_property( $identity, $properties = array(), $api_key = null ) {
	WP_Kissmetrics::init_and_identify( $api_key, $identity );
	WP_Kissmetrics::set( $properties );
}

/**
 * Helper function: Enqueue Kissmetrics JavaScript API with necessary data
 *
 * Usernames are automatically sent as the identifier for logged in users
 *
 * Optional record data from the client side:
 *   Pass $event (string) and $properties (array) to record an event
 *   Pass false (bool) and $properties (array, 1 item only) to set a property
 */
function kissmetrics_js( $event = false, $properties = array() ) {
	WP_Kissmetrics::enqueue_js();

	if ( $event && is_string( $event ) )
		WP_Kissmetrics::record_js_event( $event, $properties );
	elseif ( ! $event && 1 == count( $properties ) && ! isset( $properties[0] ) )
		WP_Kissmetrics::set_js_property( $properties );
}
