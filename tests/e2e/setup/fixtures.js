/**
 * Global setup: rebuild the fixture content on the wp-env tests instance.
 *
 * Runs after the `@wordpress/scripts` global setup has saved the admin storage
 * state. Every page and team member is deleted and recreated on each run, so
 * the suite does not depend on the monorepo's `npm run seed`.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { request } = require( '@playwright/test' );
const { RequestUtils } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	ADA,
	DRAFT,
	GRACE,
	PAGES,
	pageContent,
} = require( '../fixtures/content' );

const MEMBERS_PATH = '/wp/v2/pikari_team_member';

async function deleteAllMembers( requestUtils ) {
	const members = await requestUtils.rest( {
		path: MEMBERS_PATH,
		params: {
			per_page: 100,
			status: 'publish,future,draft,pending,private,trash',
			context: 'edit',
		},
	} );

	for ( const member of members ) {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `${ MEMBERS_PATH }/${ member.id }`,
			params: { force: true },
		} );
	}
}

async function createMember( requestUtils, member, extra = {} ) {
	return requestUtils.rest( {
		method: 'POST',
		path: MEMBERS_PATH,
		data: {
			title: member.name,
			slug: member.slug,
			status: 'publish',
			meta: member.meta,
			...extra,
		},
	} );
}

module.exports = async function fixtures( config ) {
	const buildDir = path.resolve( __dirname, '../../../build/blocks' );
	if ( ! fs.existsSync( buildDir ) ) {
		throw new Error(
			'build/blocks is missing. Run `npm run build` before `npm run test:e2e`.'
		);
	}

	const { storageState, baseURL } = config.projects[ 0 ].use;
	const requestContext = await request.newContext( { baseURL } );
	const requestUtils = new RequestUtils( requestContext, {
		storageStatePath: storageState,
	} );
	await requestUtils.setupRest();

	await requestUtils.deleteAllPosts();
	await requestUtils.deleteAllPages();
	await deleteAllMembers( requestUtils );

	const ids = {};
	ids[ ADA.slug ] = ( await createMember( requestUtils, ADA ) ).id;
	ids[ GRACE.slug ] = (
		await createMember( requestUtils, GRACE, { password: GRACE.password } )
	).id;
	ids[ DRAFT.slug ] = (
		await createMember( requestUtils, DRAFT, { status: 'draft' } )
	).id;

	const content = pageContent( ids );
	for ( const [ key, page ] of Object.entries( PAGES ) ) {
		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				title: page.title,
				slug: page.slug,
				status: 'publish',
				content: content[ key ],
			},
		} );
	}

	await requestContext.dispose();
};
