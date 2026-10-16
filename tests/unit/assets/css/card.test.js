/**
 * Tests for card.css against the markup Card_Renderer outputs.
 *
 * The contact section on /card/, the embed and the shortcode uses
 * `__contact-link`, and the address uses `__address`. For six months neither
 * matched a rule, so the links ran together ("555-0100EMAIL") in
 * browser-default blue.
 */
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const CSS = readFileSync(
	resolve( __dirname, '../../../../assets/css/card.css' ),
	'utf8'
);

// Matches Card_Renderer::render_contact() and render_address().
const MARKUP = `
	<div class="pikari-team-card">
		<div class="pikari-team-card__contact">
			<a href="tel:555-0100" class="pikari-team-card__contact-link">
				<span class="pikari-team-card__label">Phone</span>
				<span class="pikari-team-card__value">555-0100</span>
			</a>
		</div>
		<div class="pikari-team-card__address">
			<span class="pikari-team-card__label">Address</span>
			<span class="pikari-team-card__value">London, UK</span>
		</div>
	</div>
`;

describe( 'card.css', () => {
	beforeEach( () => {
		const style = document.createElement( 'style' );
		style.textContent = CSS;
		document.head.appendChild( style );
		document.body.innerHTML = MARKUP;
	} );

	it( 'lays out each contact link as a label/value row', () => {
		const link = document.querySelector( '.pikari-team-card__contact-link' );
		const style = getComputedStyle( link );

		expect( style.display ).toBe( 'flex' );
		expect( style.justifyContent ).toBe( 'space-between' );
		expect( style.textDecoration ).toBe( 'none' );
		expect( style.color ).toBe( 'inherit' );
	} );

	it( 'gives the address row the same layout and inset as the contact rows', () => {
		const address = document.querySelector( '.pikari-team-card__address' );
		const style = getComputedStyle( address );

		expect( style.display ).toBe( 'flex' );
		expect( style.justifyContent ).toBe( 'space-between' );
		expect( style.paddingLeft ).toBe( '1.5rem' );
		expect( style.paddingRight ).toBe( '1.5rem' );
	} );
} );
