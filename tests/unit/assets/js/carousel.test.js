/**
 * Tests for the standalone card carousel.
 *
 * carousel.js is a self-running script inlined into card-standalone.php, so
 * each test builds the markup Card_Renderer::render_carousel() outputs and
 * then loads the script fresh.
 */

const TWO_SLIDE_MARKUP = `
	<div class="pikari-team-card__carousel">
		<div class="pikari-team-card__carousel-track">
			<div class="pikari-team-card__slide" data-slide="0"></div>
			<div class="pikari-team-card__slide" data-slide="1"></div>
		</div>
		<div class="pikari-team-card__carousel-dots" role="tablist">
			<button class="pikari-team-card__dot active" role="tab" aria-selected="true" data-slide="0"></button>
			<button class="pikari-team-card__dot" role="tab" aria-selected="false" data-slide="1"></button>
		</div>
	</div>
`;

const ONE_SLIDE_MARKUP = `
	<div class="pikari-team-card__carousel">
		<div class="pikari-team-card__carousel-track">
			<div class="pikari-team-card__slide" data-slide="0"></div>
		</div>
		<div class="pikari-team-card__carousel-dots" role="tablist">
			<button class="pikari-team-card__dot active" role="tab" aria-selected="true" data-slide="0"></button>
		</div>
	</div>
`;

// jsdom has no IntersectionObserver; this records what the script asks of it.
let observers;

class FakeIntersectionObserver {
	constructor( callback, options ) {
		this.callback = callback;
		this.options = options;
		this.observed = [];
		observers.push( this );
	}

	observe( element ) {
		this.observed.push( element );
	}
}

function loadCarousel() {
	jest.isolateModules( () => {
		require( '../../../../assets/js/carousel.js' );
	} );
}

describe( 'carousel', () => {
	let track;
	let slides;
	let dots;

	beforeEach( () => {
		observers = [];
		window.IntersectionObserver = FakeIntersectionObserver;
	} );

	afterEach( () => {
		delete window.IntersectionObserver;
	} );

	function renderCarousel( markup ) {
		document.body.innerHTML = markup;
		track = document.querySelector( '.pikari-team-card__carousel-track' );
		slides = document.querySelectorAll( '.pikari-team-card__slide' );
		dots = document.querySelectorAll( '.pikari-team-card__dot' );
	}

	it( 'does nothing on a card without a carousel', () => {
		expect( loadCarousel ).not.toThrow();
		expect( observers ).toHaveLength( 0 );
	} );

	it( 'does not set up a carousel with a single slide', () => {
		renderCarousel( ONE_SLIDE_MARKUP );

		loadCarousel();

		expect( observers ).toHaveLength( 0 );
	} );

	it( 'watches every slide against the track', () => {
		renderCarousel( TWO_SLIDE_MARKUP );

		loadCarousel();

		expect( observers ).toHaveLength( 1 );
		expect( observers[ 0 ].options ).toEqual( {
			root: track,
			threshold: 0.5,
		} );
		expect( observers[ 0 ].observed ).toEqual( [ slides[ 0 ], slides[ 1 ] ] );
	} );

	it( 'selects the dot for the slide that scrolls into view', () => {
		renderCarousel( TWO_SLIDE_MARKUP );
		loadCarousel();

		observers[ 0 ].callback( [
			{ isIntersecting: true, target: slides[ 1 ] },
		] );

		expect( dots[ 0 ].classList.contains( 'active' ) ).toBe( false );
		expect( dots[ 0 ].getAttribute( 'aria-selected' ) ).toBe( 'false' );
		expect( dots[ 1 ].classList.contains( 'active' ) ).toBe( true );
		expect( dots[ 1 ].getAttribute( 'aria-selected' ) ).toBe( 'true' );
	} );

	it( 'leaves the selection alone when a slide scrolls out of view', () => {
		renderCarousel( TWO_SLIDE_MARKUP );
		loadCarousel();

		observers[ 0 ].callback( [
			{ isIntersecting: false, target: slides[ 1 ] },
		] );

		expect( dots[ 0 ].classList.contains( 'active' ) ).toBe( true );
		expect( dots[ 0 ].getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( dots[ 1 ].classList.contains( 'active' ) ).toBe( false );
		expect( dots[ 1 ].getAttribute( 'aria-selected' ) ).toBe( 'false' );
	} );

	it( 'scrolls the track to the slide for a clicked dot', () => {
		renderCarousel( TWO_SLIDE_MARKUP );
		// jsdom does no layout, so give the track and slide real offsets.
		Object.defineProperty( track, 'offsetLeft', { value: 20 } );
		Object.defineProperty( slides[ 1 ], 'offsetLeft', { value: 320 } );
		track.scrollTo = jest.fn();
		loadCarousel();

		dots[ 1 ].click();

		expect( track.scrollTo ).toHaveBeenCalledWith( {
			left: 300,
			behavior: 'smooth',
		} );
	} );
} );
