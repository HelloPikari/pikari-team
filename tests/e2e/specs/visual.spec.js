/**
 * Visual baselines: the card on the single member page, at desktop and
 * mobile widths. The standalone /card/ page and the card block on an ordinary
 * page are fixme until their styling gaps are decided (see below).
 *
 * Baselines are `<name>-chromium.png` under `__snapshots__/`, generated on
 * macOS / Chromium and committed. Playwright does not run on CI, so nothing
 * regenerates them automatically, and a baseline from one platform will not
 * pixel-match a render on another. Every Playwright bump changes the bundled
 * Chromium, so expect to regenerate with `npm run test:e2e -- visual.spec.js
 * --update-snapshots` and review the new images before committing them.
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

test.use( { storageState: visitor } );

const VIEWPORTS = {
	desktop: { width: 1280, height: 800 },
	mobile: { width: 390, height: 844 },
};

for ( const [ name, viewport ] of Object.entries( VIEWPORTS ) ) {
	test.describe( name, () => {
		test.use( { viewport } );

		test( 'single member page', async ( { page } ) => {
			await page.goto( memberPath( ADA ) );

			await expect( page.locator( 'main' ) ).toHaveScreenshot(
				`single-${ name }.png`,
				{ animations: 'disabled' }
			);
		} );

		// The standalone card's contact section is unstyled: Card_Renderer
		// outputs `__contact-link`, but card.css only styles `__link` (the
		// carousel slide's class), so the links run together. A baseline now
		// would lock that in. Waiting on Steve's decision; see the PR.
		test.fixme( 'standalone card', async ( { page } ) => {
			await page.goto( cardPath( ADA ) );

			await expect( page ).toHaveScreenshot( `standalone-${ name }.png`, {
				animations: 'disabled',
				fullPage: true,
			} );
		} );

		// card.css and carousel.js only load on the single member page, so a
		// card placed on an ordinary page renders unstyled. A baseline now
		// would lock that in. Waiting on Steve's decision; see the PR.
		// Fixing the enqueue alone won't do: the embed's contact section has
		// the same `__contact-link` class gap as the standalone card.
		for ( const [ view, pageFixture ] of [
			[ 'embed', PAGES.embed ],
			[ 'full', PAGES.full ],
		] ) {
			test.fixme( `card block, ${ view } view`, async ( { page } ) => {
				await page.goto( pagePath( pageFixture ) );

				await expect( page.locator( CARD ).first() ).toHaveScreenshot(
					`block-${ view }-${ name }.png`,
					{ animations: 'disabled' }
				);
			} );
		}
	} );
}
