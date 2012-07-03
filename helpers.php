<?php

/* Just a couple of simple helper functions to record basic data */

function kissmetrics_record( $id, $event, $properties = array() ) {
	global $wp_kissmetrics;

	// Setup our account and subject
	$wp_kissmetrics->init();
	$wp_kissmetrics->identify( $id );

	// Record our event
	$result = $wp_kissmetrics->record( $event, $properties );

	// Cleanup after ourselves
	$wp_kissmetrics->reset();

	return $result;
}

function kissmetrics_set( $id, $properties ) {
	global $wp_kissmetrics;

	// Setup our account and subject
	$wp_kissmetrics->init();
	$wp_kissmetrics->identify( $id );

	// Set our property
	$result = $wp_kissmetrics->set( $properties );

	// Cleanup after ourselves
	$wp_kissmetrics->reset();

	return $result;
}