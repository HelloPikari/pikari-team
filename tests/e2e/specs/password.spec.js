/**
 * Password-protected members stay hidden until a visitor unlocks them (#52).
 */
const { test, expect } = require( '@playwright/test' );
const {
	DRAFT,
	GRACE,
	cardPath,
	memberPath,
	vcardPath,
} = require( '../fixtures/content' );
const { CARD, visitor } = require( '../utils' );

test.use( { storageState: visitor } );

const PROTECTED_URLS = [
	cardPath( GRACE ),
	vcardPath( GRACE ),
	`${ cardPath( GRACE ) }manifest`,
	`${ cardPath( GRACE ) }service-worker`,
];

for ( const url of PROTECTED_URLS ) {
	test( `${ url } is a 404 while locked`, async ( { page } ) => {
		const response = await page.request.get( url );

		expect( response.status() ).toBe( 404 );
		expect( await response.text() ).not.toContain(
			GRACE.meta.pikari_team_phone
		);
	} );
}

test( 'the single page shows only the password form while locked', async ( {
	page,
} ) => {
	await page.goto( memberPath( GRACE ) );

	await expect( page.getByLabel( 'Password' ) ).toBeVisible();
	await expect( page.locator( CARD ) ).toHaveCount( 0 );
	const html = await page.content();
	expect( html ).not.toContain( GRACE.meta.pikari_team_phone );
	expect( html ).not.toContain( GRACE.meta.pikari_team_email );
} );

test( 'unlocking shows the card and opens the card URLs', async ( { page } ) => {
	await page.goto( memberPath( GRACE ) );
	await page.getByLabel( 'Password' ).fill( GRACE.password );
	await page.getByRole( 'button', { name: 'Enter' } ).click();

	await page.waitForURL( `**${ memberPath( GRACE ) }` );
	await expect( page.locator( CARD ) ).toContainText(
		GRACE.meta.pikari_team_phone
	);

	const card = await page.request.get( cardPath( GRACE ) );
	expect( card.status() ).toBe( 200 );
	// An unlocked card must not be cached for the next visitor.
	expect( card.headers()[ 'cache-control' ] ).toContain( 'no-cache' );

	const vcard = await page.request.get( vcardPath( GRACE ) );
	expect( vcard.status() ).toBe( 200 );
	expect( await vcard.text() ).toContain( `FN:${ GRACE.name }` );
} );

test( 'a draft member has no public URLs', async ( { page } ) => {
	for ( const url of [ memberPath( DRAFT ), cardPath( DRAFT ) ] ) {
		const response = await page.request.get( url );
		expect( response.status() ).toBe( 404 );
	}
} );
