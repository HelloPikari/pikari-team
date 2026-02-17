import { useBlockProps } from '@wordpress/block-editor';
import { ComboboxControl, Placeholder, Spinner } from '@wordpress/components';
import { useEntityRecords } from '@wordpress/core-data';
import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { postId } = attributes;
	const [ searchInput, setSearchInput ] = useState( '' );
	const blockProps = useBlockProps();

	const { records: teamMembers, isResolving } = useEntityRecords(
		'postType',
		'pikari_team_member',
		{
			per_page: 20,
			search: searchInput || undefined,
			_fields: 'id,title',
		}
	);

	const options = useMemo( () => {
		if ( ! teamMembers ) {
			return [];
		}
		return teamMembers.map( ( member ) => ( {
			value: member.id,
			label: member.title.rendered,
		} ) );
	}, [ teamMembers ] );

	const selectedLabel = useMemo( () => {
		if ( ! postId || ! teamMembers ) {
			return '';
		}
		const found = teamMembers.find( ( m ) => m.id === postId );
		return found ? found.title.rendered : '';
	}, [ postId, teamMembers ] );

	if ( postId ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="id-alt"
					label={ __( 'Team Member Card', 'pikari-team' ) }
					instructions={ selectedLabel || `ID: ${ postId }` }
				>
					<ComboboxControl
						label={ __( 'Change team member', 'pikari-team' ) }
						value={ postId }
						options={ options }
						onChange={ ( value ) =>
							setAttributes( { postId: value } )
						}
						onFilterValueChange={ setSearchInput }
					/>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<Placeholder
				icon="id-alt"
				label={ __( 'Team Member Card', 'pikari-team' ) }
				instructions={ __(
					'Select a team member to display their card.',
					'pikari-team'
				) }
			>
				{ isResolving && <Spinner /> }
				<ComboboxControl
					label={ __( 'Search team members', 'pikari-team' ) }
					value={ postId }
					options={ options }
					onChange={ ( value ) =>
						setAttributes( { postId: value } )
					}
					onFilterValueChange={ setSearchInput }
				/>
			</Placeholder>
		</div>
	);
}
