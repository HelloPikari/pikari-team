/**
 * The member's single page (inside the theme) and the standalone /card/ page.
 */
const { test, expect } = require( '@playwright/test' );
const { ADA, cardPath, memberPath } = require( '../fixtures/content' );
const { CARD, visitor } = require( '../utils' );

test.use( { storageState: visitor } );

test.describe( 'single member page', () => {
	test( 'renders the full card with the name as the page h1', async ( {
		page,
	} ) => {
		await page.goto( memberPath( ADA ) );

		await expect(
			page.getByRole( 'heading', { level: 1, name: ADA.name } )
		).toBeVisible();
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveCount( 1 );
		await expect( page.locator( CARD ) ).toContainText(
			ADA.meta.pikari_team_job_title
		);
		await expect(
			page.getByRole( 'link', { name: 'Save Contact' } )
		).toBeVisible();
	} );

	test( 'loads the card styles and the carousel script', async ( { page } ) => {
		await page.goto( memberPath( ADA ) );

		await expect( page.locator( '#pikari-team-card-css' ) ).toHaveCount( 1 );
		await expect( page.locator( '#pikari-team-carousel-js' ) ).toHaveCount(
			1
		);
	} );

	test( 'shows the QR code at a usable size', async ( { page } ) => {
		await page.goto( memberPath( ADA ) );

		// An SVG with no intrinsic width collapsed to 0×0 in the flex slide
		// until 1.0.4, and nothing caught it, so check the rendered box.
		const qr = page.locator( `${ CARD } .pikari-team-card__qr svg` );
		await expect( qr ).toBeVisible();
		const box = await qr.boundingBox();
		expect( box.width ).toBeGreaterThan( 100 );
		expect( box.height ).toBeGreaterThan( 100 );
	} );
} );

test.describe( 'standalone card page', () => {
	test( 'renders the card with its QR code and Save Contact', async ( {
		page,
	} ) => {
		await page.goto( cardPath( ADA ) );

		const card = page.locator( CARD );
		await expect(
			card.getByRole( 'heading', { name: ADA.name } )
		).toBeVisible();
		await expect( card ).toContainText( ADA.meta.pikari_team_email );
		await expect( card.locator( '.pikari-team-card__qr svg' ) ).toBeVisible();
		await expect(
			card.getByRole( 'link', { name: 'Save Contact' } )
		).toBeVisible();
	} );

	test( 'links its web app manifest', async ( { page } ) => {
		await page.goto( cardPath( ADA ) );

		const href = await page
			.locator( 'link[rel="manifest"]' )
			.getAttribute( 'href' );
		const response = await page.request.get( href );

		expect( response.status() ).toBe( 200 );
		expect( ( await response.json() ).name ).toContain( ADA.name );
	} );
} );
