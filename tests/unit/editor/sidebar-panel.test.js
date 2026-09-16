/**
 * Tests for the team member sidebar panel.
 *
 * The @wordpress/* editor packages are WordPress externals and aren't
 * installed, so they're replaced with minimal stand-ins: panels render as
 * sections, controls as native inputs, and the post's meta comes from a
 * mocked useEntityProp.
 */

import { act } from 'react';
import { createRoot } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useEntityProp } from '@wordpress/core-data';

import '../../../src/editor/sidebar-panel';

let mockPostType;

jest.mock( '@wordpress/plugins', () => ( { registerPlugin: jest.fn() } ), {
	virtual: true,
} );

jest.mock(
	'@wordpress/data',
	() => ( {
		useSelect: ( selector ) =>
			selector( () => ( { getCurrentPostType: () => mockPostType } ) ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/core-data', () => ( { useEntityProp: jest.fn() } ), {
	virtual: true,
} );

jest.mock(
	'@wordpress/editor',
	() => {
		const { createElement } = require( 'react' );
		return {
			PluginDocumentSettingPanel: ( { title, children } ) =>
				createElement(
					'section',
					null,
					createElement( 'h2', null, title ),
					children
				),
		};
	},
	{ virtual: true }
);

jest.mock(
	'@wordpress/components',
	() => {
		const { createElement } = require( 'react' );
		return {
			TextControl: ( { label, value, onChange } ) =>
				createElement( 'input', {
					'aria-label': label,
					value,
					onChange: ( event ) => onChange( event.target.value ),
				} ),
			SelectControl: ( { label, value, options, onChange } ) =>
				createElement(
					'select',
					{
						'aria-label': label,
						value,
						onChange: ( event ) => onChange( event.target.value ),
					},
					options.map( ( option ) =>
						createElement(
							'option',
							{ key: option.value, value: option.value },
							option.label
						)
					)
				),
		};
	},
	{ virtual: true }
);

const TeamMemberSidebar = registerPlugin.mock.calls[ 0 ][ 1 ].render;

// Sets a control's value the way a user would, so React sees the change.
function changeValue( element, value ) {
	const { set } = Object.getOwnPropertyDescriptor(
		Object.getPrototypeOf( element ),
		'value'
	);
	act( () => {
		set.call( element, value );
		element.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
}

describe( 'sidebar-panel', () => {
	let container;
	let root;
	let setMeta;

	beforeEach( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		mockPostType = 'pikari_team_member';
		setMeta = jest.fn();
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
	} );

	function renderSidebar( meta ) {
		useEntityProp.mockReturnValue( [ meta, setMeta ] );
		act( () => root.render( <TeamMemberSidebar /> ) );
	}

	function control( label ) {
		return container.querySelector( `[aria-label="${ label }"]` );
	}

	it( 'renders nothing when editing another post type', () => {
		mockPostType = 'post';

		renderSidebar( { pikari_team_first_name: 'Jane' } );

		expect( container.innerHTML ).toBe( '' );
	} );

	it( "shows the member's saved details", () => {
		renderSidebar( {
			pikari_team_first_name: 'Jane',
			pikari_team_email: 'jane@example.com',
			pikari_team_card_template: 'card-minimal',
		} );

		expect( control( 'First Name' ).value ).toBe( 'Jane' );
		expect( control( 'Email' ).value ).toBe( 'jane@example.com' );
		expect( control( 'Last Name' ).value ).toBe( '' );
		expect( control( 'Card Template' ).value ).toBe( 'card-minimal' );
	} );

	it( 'renders empty fields before the meta has loaded', () => {
		renderSidebar( undefined );

		expect( control( 'First Name' ).value ).toBe( '' );
	} );

	// Keys match Post_Type::META_FIELDS; a key that isn't registered there is
	// silently dropped by the REST API on save.
	it.each( [
		[ 'First Name', 'pikari_team_first_name' ],
		[ 'Last Name', 'pikari_team_last_name' ],
		[ 'Designation', 'pikari_team_designation' ],
		[ 'Job Title', 'pikari_team_job_title' ],
		[ 'Email', 'pikari_team_email' ],
		[ 'Phone', 'pikari_team_phone' ],
		[ 'Cell', 'pikari_team_cell' ],
		[ 'Company', 'pikari_team_company' ],
		[ 'Department', 'pikari_team_department' ],
		[ 'Website', 'pikari_team_website' ],
		[ 'Street', 'pikari_team_address_street' ],
		[ 'City', 'pikari_team_address_city' ],
		[ 'State/Province', 'pikari_team_address_state' ],
		[ 'ZIP/Postal Code', 'pikari_team_address_zip' ],
		[ 'Country', 'pikari_team_address_country' ],
		[ 'LinkedIn URL', 'pikari_team_linkedin' ],
		[ 'Twitter/X URL', 'pikari_team_twitter' ],
	] )( 'saves %s to %s', ( label, key ) => {
		renderSidebar( {} );

		changeValue( control( label ), 'New value' );

		expect( setMeta ).toHaveBeenCalledWith( { [ key ]: 'New value' } );
	} );

	it( 'keeps the other fields when one is edited', () => {
		renderSidebar( {
			pikari_team_first_name: 'Jane',
			pikari_team_last_name: 'Doe',
			pikari_team_card_template: 'card-minimal',
		} );

		changeValue( control( 'Last Name' ), 'Smith' );

		expect( setMeta ).toHaveBeenCalledWith( {
			pikari_team_first_name: 'Jane',
			pikari_team_last_name: 'Smith',
			pikari_team_card_template: 'card-minimal',
		} );
	} );

	it( 'saves a newly chosen card template', () => {
		renderSidebar( { pikari_team_first_name: 'Jane' } );

		changeValue( control( 'Card Template' ), 'card-corporate' );

		expect( setMeta ).toHaveBeenCalledWith( {
			pikari_team_first_name: 'Jane',
			pikari_team_card_template: 'card-corporate',
		} );
	} );
} );
