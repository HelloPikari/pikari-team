/**
 * Sidebar panel for team member meta fields.
 *
 * Registers a PluginDocumentSettingPanel in the block editor
 * for editing team member meta fields.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { TextControl, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const FIELD_GROUPS = [
	{
		label: __( 'Personal', 'pikari-team' ),
		fields: [
			{ key: 'pikari_team_first_name', label: __( 'First Name', 'pikari-team' ) },
			{ key: 'pikari_team_last_name', label: __( 'Last Name', 'pikari-team' ) },
			{ key: 'pikari_team_designation', label: __( 'Designation', 'pikari-team' ) },
			{ key: 'pikari_team_job_title', label: __( 'Job Title', 'pikari-team' ) },
			{ key: 'pikari_team_email', label: __( 'Email', 'pikari-team' ) },
			{ key: 'pikari_team_phone', label: __( 'Phone', 'pikari-team' ) },
			{ key: 'pikari_team_cell', label: __( 'Cell', 'pikari-team' ) },
		],
	},
	{
		label: __( 'Company', 'pikari-team' ),
		fields: [
			{ key: 'pikari_team_company', label: __( 'Company', 'pikari-team' ) },
			{ key: 'pikari_team_department', label: __( 'Department', 'pikari-team' ) },
			{ key: 'pikari_team_website', label: __( 'Website', 'pikari-team' ) },
		],
	},
	{
		label: __( 'Address', 'pikari-team' ),
		fields: [
			{ key: 'pikari_team_address_street', label: __( 'Street', 'pikari-team' ) },
			{ key: 'pikari_team_address_city', label: __( 'City', 'pikari-team' ) },
			{ key: 'pikari_team_address_state', label: __( 'State/Province', 'pikari-team' ) },
			{ key: 'pikari_team_address_zip', label: __( 'ZIP/Postal Code', 'pikari-team' ) },
			{
				key: 'pikari_team_address_country',
				label: __( 'Country', 'pikari-team' ),
			},
		],
	},
	{
		label: __( 'Social', 'pikari-team' ),
		fields: [
			{ key: 'pikari_team_linkedin', label: __( 'LinkedIn URL', 'pikari-team' ) },
			{ key: 'pikari_team_twitter', label: __( 'Twitter/X URL', 'pikari-team' ) },
		],
	},
];

const CARD_TEMPLATES = [
	{ label: __( 'Default', 'pikari-team' ), value: 'card-default' },
	{ label: __( 'Corporate', 'pikari-team' ), value: 'card-corporate' },
	{ label: __( 'Minimal', 'pikari-team' ), value: 'card-minimal' },
];

function TeamMemberSidebar() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp(
		'postType',
		'pikari_team_member',
		'meta'
	);

	if ( postType !== 'pikari_team_member' ) {
		return null;
	}

	const updateMeta = ( key, value ) => {
		setMeta( { ...meta, [ key ]: value } );
	};

	return (
		<>
			{ FIELD_GROUPS.map( ( group ) => (
				<PluginDocumentSettingPanel
					key={ group.label }
					name={ `pikari-team-${ group.label.toLowerCase() }` }
					title={ group.label }
				>
					{ group.fields.map( ( field ) => (
						<TextControl
							key={ field.key }
							label={ field.label }
							value={ meta?.[ field.key ] || '' }
							onChange={ ( value ) =>
								updateMeta( field.key, value )
							}
						/>
					) ) }
				</PluginDocumentSettingPanel>
			) ) }
			<PluginDocumentSettingPanel
				name="pikari-team-card"
				title={ __( 'Business Card', 'pikari-team' ) }
			>
				<SelectControl
					label={ __( 'Card Template', 'pikari-team' ) }
					value={ meta?.pikari_team_card_template || 'card-default' }
					options={ CARD_TEMPLATES }
					onChange={ ( value ) =>
						updateMeta( 'pikari_team_card_template', value )
					}
				/>
			</PluginDocumentSettingPanel>
		</>
	);
}

registerPlugin( 'pikari-team-sidebar', {
	render: TeamMemberSidebar,
} );
