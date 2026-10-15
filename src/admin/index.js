import { createRoot, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	FormTokenField,
	Notice,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './style.scss';

const config = window.svmAdmin || {};
const ROOT_ID = config.rootId || 'svm-settings-root';

if ( config.restNonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( config.restNonce ) );
}

function EntityPicker( { items, onChange, restPath, placeholder } ) {
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ titleIndex, setTitleIndex ] = useState( () => {
		const map = {};
		( items || [] ).forEach( ( item ) => {
			map[ item.title ] = item.id;
		} );
		return map;
	} );

	useEffect( () => {
		const map = { ...titleIndex };
		( items || [] ).forEach( ( item ) => {
			map[ item.title ] = item.id;
		} );
		setTitleIndex( map );
		// eslint-disable-next-line react-hooks/exhaustive-deps -- sync titles when server items change
	}, [ items ] );

	const onInputChange = async ( value ) => {
		if ( ! value || value.length < 2 ) {
			setSuggestions( [] );
			return;
		}

		setLoading( true );
		try {
			const results = await apiFetch( {
				path: `${ restPath }?search=${ encodeURIComponent(
					value
				) }&per_page=20&status=publish`,
			} );
			const index = { ...titleIndex };
			const labels = [];
			( results || [] ).forEach( ( entity ) => {
				const title = entity.title?.rendered
					? entity.title.rendered.replace( /<[^>]+>/g, '' )
					: `#${ entity.id }`;
				index[ title ] = entity.id;
				labels.push( title );
			} );
			setTitleIndex( index );
			setSuggestions( labels );
		} catch ( e ) {
			setSuggestions( [] );
		} finally {
			setLoading( false );
		}
	};

	const onTokensChange = ( tokens ) => {
		const nextItems = [];
		tokens.forEach( ( token ) => {
			if ( titleIndex[ token ] ) {
				nextItems.push( { id: titleIndex[ token ], title: token } );
			}
		} );
		onChange( nextItems );
	};

	return (
		<div className="svm-settings__entity-picker">
			<FormTokenField
				label=""
				value={ ( items || [] ).map( ( item ) => item.title ) }
				suggestions={ suggestions }
				onChange={ onTokensChange }
				onInputChange={ onInputChange }
				placeholder={ placeholder }
				__experimentalExpandOnFocus
				__experimentalShowHowTo={ false }
				__nextHasNoMarginBottom
			/>
			{ loading ? (
				<span className="svm-settings__spinner">
					<Spinner />
				</span>
			) : null }
		</div>
	);
}

function toggleInList( list, value, enabled ) {
	if ( enabled ) {
		return list.includes( value ) ? list : [ ...list, value ];
	}
	return list.filter( ( item ) => item !== value );
}

function SettingsApp() {
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ payload, setPayload ] = useState( null );
	const [ settings, setSettings ] = useState( null );
	const [ selectedPages, setSelectedPages ] = useState( [] );
	const [ selectedPosts, setSelectedPosts ] = useState( [] );

	useEffect( () => {
		let cancelled = false;

		( async () => {
			setLoading( true );
			setError( '' );
			try {
				const data = await apiFetch( {
					url: config.restUrl,
					method: 'GET',
				} );
				if ( cancelled ) {
					return;
				}
				setPayload( data );
				setSettings( data.settings );
				setSelectedPages( data.selected_pages || [] );
				setSelectedPosts( data.selected_posts || [] );
			} catch ( e ) {
				if ( ! cancelled ) {
					setError(
						e?.message ||
							__(
								'Could not load settings.',
								'site-visibility-manager-for-woocommerce'
							)
					);
				}
			} finally {
				if ( ! cancelled ) {
					setLoading( false );
				}
			}
		} )();

		return () => {
			cancelled = true;
		};
	}, [] );

	const save = async () => {
		if ( ! settings ) {
			return;
		}
		setSaving( true );
		setError( '' );
		setNotice( '' );
		try {
			const data = await apiFetch( {
				url: config.restUrl,
				method: 'POST',
				data: {
					...settings,
					page_ids: selectedPages.map( ( item ) => item.id ),
					post_ids: selectedPosts.map( ( item ) => item.id ),
				},
			} );
			setPayload( data );
			setSettings( data.settings );
			setSelectedPages( data.selected_pages || [] );
			setSelectedPosts( data.selected_posts || [] );
			setNotice( __( 'Settings saved.', 'site-visibility-manager-for-woocommerce' ) );
		} catch ( e ) {
			setError(
				e?.message ||
					__( 'Could not save settings.', 'site-visibility-manager-for-woocommerce' )
			);
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<div className="svm-settings__loading">
				<Spinner />
				<span>{ __( 'Loading…', 'site-visibility-manager-for-woocommerce' ) }</span>
			</div>
		);
	}

	if ( ! settings || ! payload ) {
		return (
			<div className="svm-settings">
				{ error ? (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) : null }
			</div>
		);
	}

	const status = payload.status || {};
	const links = payload.links || {};

	return (
		<div className="svm-settings">
			<div className="svm-settings__header">
				<h1>
					{ __( 'Site Visibility Manager', 'site-visibility-manager-for-woocommerce' ) }
				</h1>
				<p className="svm-settings__intro">
					{ __(
						'While WooCommerce Coming Soon is on, choose which parts of the site stay public. Design the Coming Soon experience in the Site Editor — this plugin only controls access.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
			</div>

			{ error ? (
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setError( '' ) }
				>
					{ error }
				</Notice>
			) : null }
			{ notice ? (
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setNotice( '' ) }
				>
					{ notice }
				</Notice>
			) : null }

			<div className="svm-settings__status">
				<Notice
					status={ status.coming_soon ? 'warning' : 'info' }
					isDismissible={ false }
				>
					<strong>
						{ __(
							'WooCommerce Site Visibility:',
							'site-visibility-manager-for-woocommerce'
						) }{ ' ' }
					</strong>
					{ status.coming_soon
						? __( 'Coming soon', 'site-visibility-manager-for-woocommerce' )
						: __( 'Live', 'site-visibility-manager-for-woocommerce' ) }
					{ status.coming_soon ? (
						<>
							{ ' · ' }
							{ status.store_only
								? __(
										'Store pages only',
										'site-visibility-manager-for-woocommerce'
								  )
								: __( 'Entire site', 'site-visibility-manager-for-woocommerce' ) }
						</>
					) : null }
					{ ! status.coming_soon ? (
						<p>
							{ __(
								'Settings save, but they do not affect the live site until Coming Soon is enabled in Site Visibility.',
								'site-visibility-manager-for-woocommerce'
							) }
						</p>
					) : null }
					{ status.coming_soon && status.store_only ? (
						<p>
							{ __(
								'For homepage + blog public with the shop closed, use Coming soon for the entire site (turn off “Apply to store pages only”), then enable sections below.',
								'site-visibility-manager-for-woocommerce'
							) }
						</p>
					) : null }
					<div className="svm-settings__status-actions">
						<Button
							variant="secondary"
							href={ links.site_visibility }
						>
							{ __(
								'Site Visibility settings',
								'site-visibility-manager-for-woocommerce'
							) }
						</Button>
						{ links.is_block_theme ? (
							<Button
								variant="secondary"
								href={ links.coming_soon_template }
							>
								{ __(
									'Edit Coming Soon template',
									'site-visibility-manager-for-woocommerce'
								) }
							</Button>
						) : null }
					</div>
				</Notice>
			</div>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Public sections', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<p className="svm-settings__section-help">
					{ __(
						'Cart, checkout, and account each open that WooCommerce page and its endpoints. Product catalog access is only via Shop / Products / Product taxonomies — not the post type list.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
				<div className="svm-settings__checklist">
					{ ( payload.presets || [] ).map( ( preset ) => (
						<CheckboxControl
							key={ preset.slug }
							label={ preset.label }
							help={ preset.description }
							checked={ settings.presets.includes( preset.slug ) }
							onChange={ ( checked ) =>
								setSettings( {
									...settings,
									presets: toggleInList(
										settings.presets,
										preset.slug,
										checked
									),
								} )
							}
							__nextHasNoMarginBottom
						/>
					) ) }
				</div>
			</section>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Public pages', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<p className="svm-settings__section-help">
					{ __(
						'Exact pages only — child pages are not opened automatically.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
				<EntityPicker
					items={ selectedPages }
					onChange={ setSelectedPages }
					restPath="/wp/v2/pages"
					placeholder={ __(
						'Search pages…',
						'site-visibility-manager-for-woocommerce'
					) }
				/>
			</section>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Public posts', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<p className="svm-settings__section-help">
					{ __(
						'Specific posts when “Blog posts” is too broad.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
				<EntityPicker
					items={ selectedPosts }
					onChange={ setSelectedPosts }
					restPath="/wp/v2/posts"
					placeholder={ __(
						'Search posts…',
						'site-visibility-manager-for-woocommerce'
					) }
				/>
			</section>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Public post types', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<p className="svm-settings__section-help">
					{ __(
						'Enabling a type opens its singular views, archive, and built-in taxonomies. Products are never listed here.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
				<div className="svm-settings__checklist">
					{ ( payload.post_types || [] ).map( ( type ) => (
						<CheckboxControl
							key={ type.name }
							label={ `${ type.label } (${ type.name })` }
							checked={ settings.post_types.includes( type.name ) }
							onChange={ ( checked ) =>
								setSettings( {
									...settings,
									post_types: toggleInList(
										settings.post_types,
										type.name,
										checked
									),
								} )
							}
							__nextHasNoMarginBottom
						/>
					) ) }
				</div>
			</section>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Public taxonomies', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<p className="svm-settings__section-help">
					{ __(
						'Additional taxonomy archives to open beyond the section presets.',
						'site-visibility-manager-for-woocommerce'
					) }
				</p>
				<div className="svm-settings__checklist">
					{ ( payload.taxonomies || [] ).map( ( taxonomy ) => (
						<CheckboxControl
							key={ taxonomy.name }
							label={ `${ taxonomy.label } (${ taxonomy.name })` }
							checked={ settings.taxonomy_allowlist.includes(
								taxonomy.name
							) }
							onChange={ ( checked ) =>
								setSettings( {
									...settings,
									taxonomy_allowlist: toggleInList(
										settings.taxonomy_allowlist,
										taxonomy.name,
										checked
									),
								} )
							}
							__nextHasNoMarginBottom
						/>
					) ) }
				</div>
			</section>

			<section className="svm-settings__section">
				<h2 className="svm-settings__section-title">
					{ __( 'Data', 'site-visibility-manager-for-woocommerce' ) }
				</h2>
				<div className="svm-settings__checklist">
					<CheckboxControl
						label={ __(
							'Delete plugin settings on uninstall',
							'site-visibility-manager-for-woocommerce'
						) }
						checked={ !! settings.delete_data_on_uninstall }
						onChange={ ( checked ) =>
							setSettings( {
								...settings,
								delete_data_on_uninstall: checked,
							} )
						}
						__nextHasNoMarginBottom
					/>
				</div>
			</section>

			<div className="svm-settings__actions">
				<Button
					variant="primary"
					onClick={ save }
					isBusy={ saving }
					disabled={ saving }
				>
					{ __( 'Save changes', 'site-visibility-manager-for-woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}

const rootEl = document.getElementById( ROOT_ID );
if ( rootEl ) {
	createRoot( rootEl ).render( <SettingsApp /> );
}
