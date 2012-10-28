// Make sure the 'wp' global object exists
window.wp = window.wp || {};

// From Kissmetrics' default JS
var _kmq = _kmq || [];
function _kms(u){
	setTimeout(function(){
		var d = document,
			f = d.getElementsByTagName( 'script' )[ 0 ],
			s = d.createElement( 'script' );

		s.type  = 'text/javascript';
		s.async = true;
		s.src   = u;

		f.parentNode.insertBefore( s, f );
	}, 1);
}

// Helper functions
(function($){
	wp.kissmetrics = {
		// name should be a string
		// e.g. 'Clicked signup button'
		// properties should be an array of key/value pair objects
		// e.g. [ { experiment: 'domain form' }, { version: 'original' } ]
		recordEvent: function( name, properties ) {
			var prefixedProperties = {};
			properties = properties || {};

			$.each( properties, function( property, value ) {
				prefixedProperties[ name + ' | ' + property ] = value;
			});

			_kmq.push( [ 'record', name, prefixedProperties ] );
		},

		// Property should be a single key/value pair object
		// e.g. { location: 'San Francisco' }
		setProperty: function( property ) {
			_kmq.push( [ 'set', property ] );
		},

		// Handles API setup and events/properties in the DOM on ready
		init: function() {
			var kissmetrics = window.kissmetrics || {},
				events = window.kissmetrics_events || {},
				properties = window.kissmetrics_properties || {};

			// Remaining functions use data populated by kissmetrics_js() in PHP

			// Need this to send data anywhere
			if ( ! kissmetrics.api_key )
				return;

			_kms( '//i.kissmetrics.com/i.js' );
			_kms( '//doug1izaerwt3.cloudfront.net/' + kissmetrics.api_key + '.1.js' );

			// Use wp usernames to identify users
			if ( kissmetrics.username )
				_kmq.push( [ 'identify', kissmetrics.username ] );

			// Record arbitrary events
			$.each( events, function() {
				wp.kissmetrics.recordEvent( this.name, this.properties );
			});

			// Set arbitrary properties
			$.each( properties, function() {
				wp.kissmetrics.setProperty( this );
			});
		}
	};

	// Bind init to $(document).ready()
	$( wp.kissmetrics.init );
}(jQuery));