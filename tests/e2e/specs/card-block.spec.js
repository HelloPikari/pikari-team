/**
 * The Card block and shortcode on an ordinary page: each view shows the
 * member's fields, and members a visitor may not see render nothing.
 */
const { test, expect } = require( '@playwright/test' );
const { ADA, DRAFT, GRACE, PAGES, pagePath } = require( '../fixtures/content' );
const { CARD, visitor } = require( '../utils' );

test.use( { storageState: visitor } );

test.describe( 'embed view', () => {
	test( 'the block and the shortcode both show the contact fields', async ( {
		page,
	} ) => {
		await page.goto( pagePath( PAGES.embed ) );

		const cards = page.locator( CARD );
		await expect( cards ).toHaveCount( 2 );

		for ( const card of await cards.all() ) {
			await expect(
				card.getByRole( 'heading', { level: 2, name: ADA.name } )
			).toBeVisible();
			await expect( card ).toContainText( ADA.meta.pikari_team_job_title );
			await expect( card ).toContainText( ADA.meta.pikari_team_company );
			await expect(
				card.getByRole( 'link', { name: /Phone/ } )
			).toHaveAttribute( 'href', `tel:${ ADA.meta.pikari_team_phone }` );
			await expect(
				card.getByRole( 'link', { name: /Email/ } )
			).toHaveAttribute( 'href', `mailto:${ ADA.meta.pikari_team_email }` );
		}
	} );

	test( 'leaves out the QR code and the Save Contact button', async ( {
		page,
	} ) => {
		await page.goto( pagePath( PAGES.embed ) );

		await expect( page.locator( `${ CARD } svg` ) ).toHaveCount( 0 );
		await expect(
			page.getByRole( 'link', { name: 'Save Contact' } )
		).toHaveCount( 0 );
	} );
} );

test( 'full view shows the QR code, contact slide and Save Contact', async ( {
	page,
} ) => {
	await page.goto( pagePath( PAGES.full ) );

	const card = page.locator( CARD );
	await expect( card ).toHaveCount( 1 );
	await expect( card.locator( '.pikari-team-card__name' ) ).toHaveText(
		ADA.name
	);
	await expect( card.locator( '.pikari-team-card__qr svg' ) ).toHaveCount( 1 );
	await expect( card ).toContainText( ADA.meta.pikari_team_phone );
	await expect( card ).toContainText( 'London, UK' );
	await expect(
		card.getByRole( 'link', { name: 'Save Contact' } )
	).toHaveAttribute( 'href', /\/card\/ada-lovelace\/download\.vcf$/ );
} );

test( 'protected and draft members render no card', async ( { page } ) => {
	await page.goto( pagePath( PAGES.hidden ) );

	// The page itself rendered, so an empty result means the cards were withheld.
	await expect( page.getByText( 'Hidden cards page.' ) ).toBeVisible();
	await expect( page.locator( CARD ) ).toHaveCount( 0 );
	for ( const member of [ GRACE, DRAFT ] ) {
		await expect( page.getByText( member.name ) ).toHaveCount( 0 );
		await expect(
			page.getByText( member.meta.pikari_team_email )
		).toHaveCount( 0 );
	}
} );
