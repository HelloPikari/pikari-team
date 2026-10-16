/**
 * Without JavaScript the cards are fully usable: everything is server
 * rendered, so contact details, the QR code and Save Contact all work.
 */
const { test, expect } = require( '@playwright/test' );
const {
	ADA,
	PAGES,
	cardPath,
	memberPath,
	pagePath,
} = require( '../fixtures/content' );
const { CARD, visitor } = require( '../utils' );

test.use( { javaScriptEnabled: false, storageState: visitor } );

for ( const [ label, url ] of [
	[ 'single member page', memberPath( ADA ) ],
	[ 'standalone card', cardPath( ADA ) ],
] ) {
	test( `the ${ label } shows every contact detail and the QR code`, async ( {
		page,
	} ) => {
		await page.goto( url );

		const card = page.locator( CARD );
		await expect( card.locator( '.pikari-team-card__qr svg' ) ).toBeVisible();
		await expect(
			card.getByRole( 'link', { name: /Phone/ } )
		).toHaveAttribute( 'href', `tel:${ ADA.meta.pikari_team_phone }` );
		await expect(
			card.getByRole( 'link', { name: /Email/ } )
		).toHaveAttribute( 'href', `mailto:${ ADA.meta.pikari_team_email }` );
		await expect(
			card.getByRole( 'link', { name: 'Save Contact' } )
		).toHaveAttribute( 'href', /\/card\/ada-lovelace\/download\.vcf$/ );
	} );
}

test( 'the embed shows its contact links', async ( { page } ) => {
	await page.goto( pagePath( PAGES.embed ) );

	await expect( page.locator( CARD ).first() ).toContainText(
		ADA.meta.pikari_team_email
	);
} );

test( 'the contact slide can be reached by scrolling the carousel', async ( {
	page,
} ) => {
	await page.goto( memberPath( ADA ) );

	const contact = page.locator( '.pikari-team-card__slide[data-slide="1"]' );
	await contact.scrollIntoViewIfNeeded();

	await expect( contact.getByRole( 'link', { name: /Email/ } ) ).toBeInViewport();
} );
