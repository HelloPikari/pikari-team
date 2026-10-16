/**
 * Fixture content shared by the global setup and the specs.
 *
 * Paths assume the plugin defaults: members at /team/{slug}/ and cards at
 * /card/{slug}/.
 */

const ADA = {
	slug: 'ada-lovelace',
	name: 'Ada Lovelace',
	meta: {
		pikari_team_first_name: 'Ada',
		pikari_team_last_name: 'Lovelace',
		pikari_team_job_title: 'Lead Analyst',
		pikari_team_company: 'Analytical Engines Ltd',
		pikari_team_phone: '555-0100',
		pikari_team_email: 'ada@example.com',
		pikari_team_website: 'https://example.com/ada',
		pikari_team_address_city: 'London',
		pikari_team_address_country: 'UK',
	},
};

const GRACE = {
	slug: 'grace-hopper',
	name: 'Grace Hopper',
	password: 'cobol',
	meta: {
		pikari_team_first_name: 'Grace',
		pikari_team_last_name: 'Hopper',
		pikari_team_job_title: 'Rear Admiral',
		pikari_team_phone: '555-0199',
		pikari_team_email: 'grace@example.com',
	},
};

const DRAFT = {
	slug: 'draft-member',
	name: 'Dana Draft',
	meta: {
		pikari_team_first_name: 'Dana',
		pikari_team_last_name: 'Draft',
		pikari_team_email: 'dana@example.com',
	},
};

const memberPath = ( member ) => `/team/${ member.slug }/`;
const cardPath = ( member ) => `/card/${ member.slug }/`;
const vcardPath = ( member ) => `/card/${ member.slug }/download.vcf`;

// Pages that host cards. `content` is filled in by the setup once the
// member IDs are known.
const PAGES = {
	embed: { slug: 'card-embed', title: 'Card Embed' },
	full: { slug: 'card-full', title: 'Card Full View' },
	hidden: { slug: 'card-hidden', title: 'Hidden Cards' },
};

const pagePath = ( page ) => `/${ page.slug }/`;

const cardBlock = ( attributes ) =>
	`<!-- wp:pikari-team/card ${ JSON.stringify( attributes ) } /-->`;

/**
 * Block content for each fixture page.
 *
 * @param {Object<string, number>} ids Member post IDs keyed by slug.
 * @return {Object<string, string>} Content keyed by page key.
 */
const pageContent = ( ids ) => ( {
	embed: [
		cardBlock( { postId: ids[ ADA.slug ] } ),
		'<!-- wp:shortcode -->',
		`[pikari_team_card slug="${ ADA.slug }"]`,
		'<!-- /wp:shortcode -->',
	].join( '\n' ),
	full: cardBlock( { postId: ids[ ADA.slug ], view: 'full' } ),
	hidden: [
		'<!-- wp:paragraph -->\n<p>Hidden cards page.</p>\n<!-- /wp:paragraph -->',
		cardBlock( { postId: ids[ GRACE.slug ] } ),
		cardBlock( { postId: ids[ GRACE.slug ], view: 'full' } ),
		cardBlock( { postId: ids[ DRAFT.slug ] } ),
		'<!-- wp:shortcode -->',
		`[pikari_team_card slug="${ GRACE.slug }"]`,
		'<!-- /wp:shortcode -->',
	].join( '\n' ),
} );

module.exports = {
	ADA,
	DRAFT,
	GRACE,
	PAGES,
	cardPath,
	memberPath,
	pageContent,
	pagePath,
	vcardPath,
};
