/* global pikariSwUrl */
if ( 'serviceWorker' in navigator && typeof pikariSwUrl !== 'undefined' ) {
	navigator.serviceWorker.register( pikariSwUrl )
		.then( function() {
			return navigator.serviceWorker.ready;
		} )
		.then( function( registration ) {
			registration.active.postMessage( {
				action: 'cache-page',
				url: window.location.pathname,
			} );
		} )
		.catch( function( error ) {
			// eslint-disable-next-line no-console
			console.error( 'SW registration failed:', error );
		} );
}
