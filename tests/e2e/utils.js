/**
 * Helpers shared by the end-to-end specs.
 */

// Browse as a logged-out visitor rather than the admin storage state. A
// logged-in admin can read drafts and protected posts, so it would hide
// exactly the access rules these specs check.
const visitor = { cookies: [], origins: [] };

const CARD = '.pikari-team-card';

module.exports = {
	CARD,
	visitor,
};
