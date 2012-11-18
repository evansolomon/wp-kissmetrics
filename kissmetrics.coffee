# Fallback for Kissmetrics global
window._kmq ?= []

# Helper functions
( ( $, global ) ->
	global.kissmetrics =

		# Script loader
		_kms: ( url ) ->
			setTimeout ->
				d = document
				f = d.getElementsByTagName( 'script' )[0]

				s = d.createElement 'script'
				$.extend s,
					type : 'text/javascript'
					async: true
					src  : url

				f.parentNode.insertBefore s, f
			, 1

		# name should be a string
		# e.g. 'Clicked signup button'
		# properties should be an array of key/value pair objects
		# e.g. [ { experiment: 'domain form' }, { version: 'original' } ]
		recordEvent: ( name, properties = {} )->
			prefixedProperties = {}
			$.each properties, ( property, value ) ->
				prefixedProperties[ "#{name}|#{property}" ] = value;

			_kmq.push [ 'record', name, prefixedProperties ]

		# Property should be a single key/value pair object
		# e.g. { location: 'San Francisco' }
		setProperty: ( property ) ->
			_kmq.push [ 'set', property ]

		# Handles API setup and events/properties in the DOM on ready
		init: ->
			kissmetrics = window.kissmetrics_api || {}
			queries     = window.kissmetrics_queries || {}
			events      = queries.events || {}
			properties  = queries.properties || {}

			# // Need this to send data anywhere
			return null if !kissmetrics

			@_kms '//i.kissmetrics.com/i.js'
			@_kms "//doug1izaerwt3.cloudfront.net/#{kissmetrics.api_key}.1.js"

			# Use wp usernames to identify users
			_kmq.push [ 'identify', kissmetrics.username ] if kissmetrics.username

			# Record arbitrary events
			$.each events, ->
				global.kissmetrics.recordEvent @name, @properties

			# Set arbitrary properties
			$.each properties, ->
				global.kissmetrics.setProperty @

	# Bind init to $(document).ready() in its current context
	$( $.proxy global.kissmetrics.init, global.kissmetrics )
)( jQuery, window )
