/**
 * The QR / contact carousel on the single member page.
 */
const { test, expect } = require( '@playwright/test' );
const { ADA, memberPath } = require( '../fixtures/content' );
const { visitor } = require( '../utils' );

test.use( { storageState: visitor } );

const track = ( page ) => page.locator( '.pikari-team-card__carousel-track' );
const dot = ( page, name ) => page.getByRole( 'tab', { name } );

test.beforeEach( async ( { page } ) => {
	await page.goto( memberPath( ADA ) );
} );

test( 'starts on the QR slide', async ( { page } ) => {
	await expect( dot( page, 'QR Code' ) ).toHaveAttribute(
		'aria-selected',
		'true'
	);
	await expect( dot( page, 'Contact Info' ) ).toHaveAttribute(
		'aria-selected',
		'false'
	);
	expect( await track( page ).evaluate( ( el ) => el.scrollLeft ) ).toBe( 0 );
} );

test( 'clicking a dot scrolls to its slide and selects it', async ( {
	page,
} ) => {
	await dot( page, 'Contact Info' ).click();

	await expect
		.poll( () => track( page ).evaluate( ( el ) => el.scrollLeft ) )
		.toBeGreaterThan( 0 );
	await expect( dot( page, 'Contact Info' ) ).toHaveAttribute(
		'aria-selected',
		'true'
	);
	await expect( dot( page, 'QR Code' ) ).toHaveAttribute(
		'aria-selected',
		'false'
	);

	await dot( page, 'QR Code' ).click();

	await expect
		.poll( () => track( page ).evaluate( ( el ) => el.scrollLeft ) )
		.toBe( 0 );
	await expect( dot( page, 'QR Code' ) ).toHaveAttribute(
		'aria-selected',
		'true'
	);
} );

test( 'the dots are reachable by Tab and work with Enter and Space', async ( {
	page,
} ) => {
	// Shift+Tab back from Save Contact proves the dot is in the tab order.
	await page.getByRole( 'link', { name: 'Save Contact' } ).focus();
	await page.keyboard.press( 'Shift+Tab' );
	await expect( dot( page, 'Contact Info' ) ).toBeFocused();

	await page.keyboard.press( 'Enter' );
	await expect( dot( page, 'Contact Info' ) ).toHaveAttribute(
		'aria-selected',
		'true'
	);

	await page.keyboard.press( 'Shift+Tab' );
	await expect( dot( page, 'QR Code' ) ).toBeFocused();
	await page.keyboard.press( 'Space' );
	await expect( dot( page, 'QR Code' ) ).toHaveAttribute(
		'aria-selected',
		'true'
	);
} );

// The dots claim role="tab" but have no arrow-key support or tabpanels.
// Roadmap: "Carousel a11y". Unfixme this when that lands.
test.fixme( 'arrow keys move between the tabs', async ( { page } ) => {
	await dot( page, 'QR Code' ).focus();
	await page.keyboard.press( 'ArrowRight' );

	await expect( dot( page, 'Contact Info' ) ).toBeFocused();
} );
