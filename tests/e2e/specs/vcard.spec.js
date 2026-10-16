/**
 * The vCard download: the file itself, and the Save Contact button on the
 * single page that fetches it.
 */
const { test, expect } = require( '@playwright/test' );
const { ADA, memberPath, vcardPath } = require( '../fixtures/content' );
const { visitor } = require( '../utils' );

test.use( { storageState: visitor } );

test( 'serves a vCard 3.0 attachment with the member fields', async ( {
	page,
} ) => {
	const response = await page.request.get( vcardPath( ADA ) );

	expect( response.status() ).toBe( 200 );
	expect( response.headers()[ 'content-type' ] ).toContain( 'text/vcard' );
	expect( response.headers()[ 'content-disposition' ] ).toBe(
		'attachment; filename="Ada-Lovelace.vcf"'
	);

	const vcard = await response.text();
	expect( vcard ).toMatch( /^BEGIN:VCARD\r?\nVERSION:3\.0\r?\n/ );
	expect( vcard ).toContain( `FN:${ ADA.name }` );
	expect( vcard ).toContain( `TEL;TYPE=WORK,VOICE:${ ADA.meta.pikari_team_phone }` );
	expect( vcard ).toContain( `EMAIL;TYPE=INTERNET:${ ADA.meta.pikari_team_email }` );
	expect( vcard.trimEnd() ).toMatch( /END:VCARD$/ );
} );

test( 'Save Contact downloads the vCard', async ( { page } ) => {
	await page.goto( memberPath( ADA ) );

	const [ download ] = await Promise.all( [
		page.waitForEvent( 'download' ),
		page.getByRole( 'link', { name: 'Save Contact' } ).click(),
	] );

	expect( download.suggestedFilename() ).toBe( 'Ada-Lovelace.vcf' );
} );
