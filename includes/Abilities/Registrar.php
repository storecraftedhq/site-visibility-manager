<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager\Abilities;

use StoreCrafted\SiteVisibilityManager\AllowlistSettings;
use StoreCrafted\SiteVisibilityManager\Rest\SettingsController;
use WP_Error;
use WP_REST_Response;

/**
 * Registers Abilities API category and MVP read/write abilities.
 */
final class Registrar {

	public const CATEGORY_ID = 'site-visibility-manager-for-woocommerce';

	public const ABILITY_GET_ALLOWLIST_STATUS = 'site-visibility-manager-for-woocommerce/get-allowlist-status';

	public const ABILITY_UPDATE_ALLOWLIST_SETTINGS = 'site-visibility-manager-for-woocommerce/update-allowlist-settings';

	public function register(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_ID,
			array(
				'label'       => __( 'Site Visibility Manager', 'site-visibility-manager-for-woocommerce' ),
				'description' => __(
					'Read and update the public allowlist used while WooCommerce Coming Soon is active.',
					'site-visibility-manager-for-woocommerce'
				),
			)
		);
	}

	public function register_abilities(): void {
		wp_register_ability(
			self::ABILITY_GET_ALLOWLIST_STATUS,
			array(
				'label'               => __( 'Get allowlist status', 'site-visibility-manager-for-woocommerce' ),
				'description'         => __(
					'Returns Coming Soon status and the current public allowlist settings, including preset and catalog metadata.',
					'site-visibility-manager-for-woocommerce'
				),
				'category'            => self::CATEGORY_ID,
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'execute_get_allowlist_status' ),
				'output_schema'       => $this->get_allowlist_status_output_schema(),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		wp_register_ability(
			self::ABILITY_UPDATE_ALLOWLIST_SETTINGS,
			array(
				'label'               => __( 'Update allowlist settings', 'site-visibility-manager-for-woocommerce' ),
				'description'         => __(
					'Full-body replace of the public allowlist settings (presets, page/post IDs, post types, taxonomies, uninstall flag). Omitted keys sanitize to empty or defaults — send a complete settings object, not a patch.',
					'site-visibility-manager-for-woocommerce'
				),
				'category'            => self::CATEGORY_ID,
				'input_schema'        => $this->get_allowlist_settings_input_schema(),
				'output_schema'       => $this->get_allowlist_status_output_schema(),
				'permission_callback' => array( $this, 'can_manage' ),
				'execute_callback'    => array( $this, 'execute_update_allowlist_settings' ),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * @param mixed $input Ability input (unused for permission check).
	 */
	public function can_manage( mixed $input = null ): bool {
		unset( $input );
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * @param mixed $input Ability input (unused for this zero-arg read).
	 * @return array<string, mixed>
	 */
	public function execute_get_allowlist_status( mixed $input = null ): array {
		unset( $input );

		$response = ( new SettingsController() )->get_settings();
		if ( $response instanceof WP_REST_Response ) {
			$data = $response->get_data();
			return is_array( $data ) ? $data : array();
		}

		return is_array( $response ) ? $response : array();
	}

	/**
	 * Persist allowlist settings via the same sanitize path as the settings REST.
	 *
	 * @param mixed $input Full settings object (not a patch).
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute_update_allowlist_settings( mixed $input = null ) {
		$result = SettingsController::normalize_update_body( $input );
		if ( ! $result['ok'] ) {
			return new WP_Error(
				$result['code'],
				__( 'Invalid settings payload.', 'site-visibility-manager-for-woocommerce' )
			);
		}

		update_option( AllowlistSettings::OPTION_KEY, $result['settings'], false );

		return $this->execute_get_allowlist_status();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_allowlist_settings_input_schema(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'presets'                  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __(
						'Known preset slugs to keep public (unknown values are dropped).',
						'site-visibility-manager-for-woocommerce'
					),
				),
				'page_ids'                 => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'post_ids'                 => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'post_types'               => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __(
						'Public post type slugs (product and attachment are never persisted).',
						'site-visibility-manager-for-woocommerce'
					),
				),
				'taxonomy_allowlist'       => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'delete_data_on_uninstall' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_allowlist_status_output_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'settings', 'status' ),
			'properties' => array(
				'settings'       => array(
					'type'       => 'object',
					'properties' => array(
						'presets'                  => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'page_ids'                 => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'post_ids'                 => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'post_types'               => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'taxonomy_allowlist'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'delete_data_on_uninstall' => array( 'type' => 'boolean' ),
					),
				),
				'status'         => array(
					'type'       => 'object',
					'properties' => array(
						'coming_soon' => array( 'type' => 'boolean' ),
						'store_only'  => array( 'type' => 'boolean' ),
					),
				),
				'links'          => array( 'type' => 'object' ),
				'selected_pages' => array( 'type' => 'array' ),
				'selected_posts' => array( 'type' => 'array' ),
				'post_types'     => array( 'type' => 'array' ),
				'taxonomies'     => array( 'type' => 'array' ),
				'presets'        => array( 'type' => 'array' ),
			),
		);
	}
}
