/**
 * Tests for the standalone card's service worker registration.
 *
 * sw-register.js is inlined into card-standalone.php after the template
 * declares `pikariSwUrl`, and shares its <script> tag with carousel.js — so
 * anything it throws also stops the carousel. Each test sets the globals the
 * page provides and then loads the script fresh.
 */

const SW_URL = '/card/jane-doe/service-worker';

function loadSwRegister() {
	jest.isolateModules( () => {
		require( '../../../../assets/js/sw-register.js' );
	} );
}

function flushPromises() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'sw-register', () => {
	let serviceWorker;
	let activeWorker;

	beforeEach( () => {
		activeWorker = { postMessage: jest.fn() };
		serviceWorker = {
			// On a first visit the new worker is still installing, so the
			// registration has no active worker until `ready` resolves.
			register: jest.fn().mockResolvedValue( { active: null } ),
			ready: Promise.resolve( { active: activeWorker } ),
		};
		Object.defineProperty( window.navigator, 'serviceWorker', {
			value: serviceWorker,
			configurable: true,
		} );
		window.pikariSwUrl = SW_URL;
		window.history.pushState( {}, '', '/card/jane-doe/' );
	} );

	afterEach( () => {
		delete window.navigator.serviceWorker;
		delete window.pikariSwUrl;
	} );

	it( "registers the card's service worker", () => {
		loadSwRegister();

		expect( serviceWorker.register ).toHaveBeenCalledWith( SW_URL );
	} );

	it( 'asks the ready worker to cache the current card page', async () => {
		loadSwRegister();
		await flushPromises();

		expect( activeWorker.postMessage ).toHaveBeenCalledWith( {
			action: 'cache-page',
			url: '/card/jane-doe/',
		} );
	} );

	it( 'does not register without a service worker URL', () => {
		delete window.pikariSwUrl;

		expect( loadSwRegister ).not.toThrow();
		expect( serviceWorker.register ).not.toHaveBeenCalled();
	} );

	it( 'does nothing in browsers without service worker support', () => {
		delete window.navigator.serviceWorker;

		expect( loadSwRegister ).not.toThrow();
	} );

	it( 'logs a failed registration instead of rejecting', async () => {
		const error = new Error( 'Registration blocked' );
		serviceWorker.register.mockRejectedValue( error );

		loadSwRegister();
		await flushPromises();

		expect( console ).toHaveErroredWith( 'SW registration failed:', error );
		expect( activeWorker.postMessage ).not.toHaveBeenCalled();
	} );
} );
