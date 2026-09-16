/**
 * Tests for the Team Member Card block's editor UI.
 *
 * The @wordpress/* editor packages are WordPress externals and aren't
 * installed, so they're replaced with minimal stand-ins. The combobox becomes
 * a search box plus one button per option, and team members come from a
 * mocked useEntityRecords.
 */

import { act } from 'react';
import { createRoot } from '@wordpress/element';
import { useEntityRecords } from '@wordpress/core-data';

import Edit from '../../../../src/blocks/card/edit';

jest.mock(
	'@wordpress/block-editor',
	() => ( { useBlockProps: () => ( {} ) } ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/core-data',
	() => ( { useEntityRecords: jest.fn() } ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/components',
	() => {
		const { createElement } = require( 'react' );
		return {
			Placeholder: ( { label, instructions, children } ) =>
				createElement(
					'div',
					null,
					createElement( 'h2', null, label ),
					createElement( 'p', null, instructions ),
					children
				),
			Spinner: () => createElement( 'span', { role: 'progressbar' } ),
			ComboboxControl: ( { label, options, onChange, onFilterValueChange } ) =>
				createElement(
					'div',
					{ role: 'group', 'aria-label': label },
					createElement( 'input', {
						type: 'search',
						onChange: ( event ) =>
							onFilterValueChange( event.target.value ),
					} ),
					options.map( ( option ) =>
						createElement(
							'button',
							{
								key: option.value,
								type: 'button',
								onClick: () => onChange( option.value ),
							},
							option.label
						)
					)
				),
		};
	},
	{ virtual: true }
);

// Shaped like a `_fields: 'id,title'` response in the edit context.
const MEMBERS = [
	{ id: 7, title: { raw: 'Jane Doe', rendered: 'Jane Doe' } },
	{ id: 9, title: { raw: 'Sam Lee', rendered: 'Sam Lee' } },
];

// Sets an input's value the way a user would, so React sees the change.
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

describe( 'edit', () => {
	let container;
	let root;
	let setAttributes;

	beforeEach( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		setAttributes = jest.fn();
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
	} );

	function renderEdit( postId, { records = MEMBERS, isResolving = false } = {} ) {
		useEntityRecords.mockReturnValue( { records, isResolving } );
		act( () =>
			root.render(
				<Edit
					attributes={ { postId } }
					setAttributes={ setAttributes }
				/>
			)
		);
	}

	function optionLabels() {
		return Array.from( container.querySelectorAll( 'button' ) ).map(
			( button ) => button.textContent
		);
	}

	function clickOption( label ) {
		const button = Array.from( container.querySelectorAll( 'button' ) ).find(
			( candidate ) => candidate.textContent === label
		);
		act( () => button.click() );
	}

	function instructions() {
		return container.querySelector( 'p' ).textContent;
	}

	it( 'lists team members to choose from', () => {
		renderEdit( 0 );

		expect( optionLabels() ).toEqual( [ 'Jane Doe', 'Sam Lee' ] );
	} );

	it( 'renders an empty list before team members load', () => {
		renderEdit( 0, { records: null, isResolving: true } );

		expect( optionLabels() ).toEqual( [] );
	} );

	it( "stores the chosen member's ID", () => {
		renderEdit( 0 );

		clickOption( 'Sam Lee' );

		expect( setAttributes ).toHaveBeenCalledWith( { postId: 9 } );
	} );

	it( 'searches team members by the typed text', () => {
		renderEdit( 0 );

		expect( useEntityRecords ).toHaveBeenLastCalledWith(
			'postType',
			'pikari_team_member',
			{ per_page: 20, search: undefined, _fields: 'id,title' }
		);

		changeValue( container.querySelector( 'input' ), 'sam' );

		expect( useEntityRecords ).toHaveBeenLastCalledWith(
			'postType',
			'pikari_team_member',
			{ per_page: 20, search: 'sam', _fields: 'id,title' }
		);
	} );

	it( 'shows a spinner while team members load', () => {
		renderEdit( 0, { records: null, isResolving: true } );

		expect( container.querySelector( '[role="progressbar"]' ) ).not.toBeNull();
	} );

	it( 'hides the spinner once team members have loaded', () => {
		renderEdit( 0 );

		expect( container.querySelector( '[role="progressbar"]' ) ).toBeNull();
	} );

	it( 'names the selected team member', () => {
		renderEdit( 9 );

		expect( instructions() ).toBe( 'Sam Lee' );
	} );

	it( 'shows the ID when the selected member is not in the results', () => {
		renderEdit( 42 );

		expect( instructions() ).toBe( 'ID: 42' );
	} );

	it( 'shows the ID while team members are still loading', () => {
		renderEdit( 42, { records: null, isResolving: true } );

		expect( instructions() ).toBe( 'ID: 42' );
	} );

	it( 'lets a selected member be swapped for another', () => {
		renderEdit( 9 );

		clickOption( 'Jane Doe' );

		expect( setAttributes ).toHaveBeenCalledWith( { postId: 7 } );
	} );
} );
