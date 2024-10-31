import '../css/admin.scss';
/**
 * Run the script when dom is ready.
 */

/* global wpforms_admin, jconfirm, wpCookies, Choices, List, wpf, google */

;( function( $ ) {

	'use strict';

	// Admin object.
	const PollifyAdmin = {

		/**
		 * Start the engine.
		 *
		 * @since 1.3.9
		 */
		init: function() {
			// Document ready.
			$( PollifyAdmin.ready );

			// Load the Google Charts API.
			PollifyAdmin.loadGoogleCharts();
		},

		/**
		 * Document ready.
		 */
		ready: function() {

			// If there are screen options we have to move them.
			$( '#screen-meta-links, #screen-meta' ).prependTo( '#wp-pollify-header-screen' ).show();
		},

		/**
		 * Draw the regions map.
		 *
		 * @since 1.3.9
		 * @return void
		 */
		drawRegionsMap: function() {
			var geoChartMap = document.getElementById( 'geo-chart-map' );
			var locationVotes = JSON.parse( geoChartMap.dataset.locations );

			var data = google.visualization.arrayToDataTable( locationVotes );

			var options = {
				colorAxis: {colors: ['#91cdff', '#2271b1']},
				magnifyingGlass: {enable: true, zoomFactor: 15},
			};

			var chart = new google.visualization.GeoChart(geoChartMap);

			chart.draw( data, options );
		},

		/**
		 * Load the Google Charts API.
		 *
		 * @since 1.3.9
		 */
		loadGoogleCharts: function() {
			if ( document.getElementById( 'geo-chart-map' ) ) {
				google.charts.load('current', {
					'packages':['geochart'],
				});

				google.charts.setOnLoadCallback(PollifyAdmin.drawRegionsMap);
			}
		},
	};

	PollifyAdmin.init();

} )( jQuery );
