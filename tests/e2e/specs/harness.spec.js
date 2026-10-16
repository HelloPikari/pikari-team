/**
 * Smoke test: every fixture URL resolves, so a failure elsewhere is about
 * behaviour rather than a missing page or broken permalinks.
 */
const { test, expect } = require( '@playwright/test' );
const {
	ADA,
	PAGES,
	cardPath,
	memberPath,
	pagePath,
} = require( '../fixtures/content' );
const { visitor } = require( '../utils' );

test.use( { storageState: visitor } );

const URLS = [
	memberPath( ADA ),
	cardPath( ADA ),
	...Object.values( PAGES ).map( pagePath ),
];

for ( const url of URLS ) {
	test( `${ url } returns 200`, async ( { page } ) => {
		const response = await page.goto( url );

		expect( response.status() ).toBe( 200 );
	} );
}
