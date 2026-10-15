<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager\Rest;

use StoreCrafted\SiteVisibilityManager\AllowlistSettings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class SettingsController {

	public const NAMESPACE = 'site-visibility-manager/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Normalize a REST update body without WordPress globals (unit-testable).
	 *
	 * @param mixed $params Raw JSON body.
	 * @return array{ok:true,settings:array<string,mixed>}|array{ok:false,code:string,message:string}
	 */
	public static function normalize_update_body( mixed $params ): array {
		if ( ! is_array( $params ) ) {
			return array(
				'ok'      => false,
				'code'    => 'svm_invalid_settings',
				'message' => 'Invalid settings payload.',
			);
		}

		return array(
			'ok'       => true,
			'settings' => AllowlistSettings::sanitize( $params ),
		);
	}

	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->build_payload( AllowlistSettings::get() ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		$result = self::normalize_update_body( $request->get_json_params() );
		if ( ! $result['ok'] ) {
			return new WP_Error(
				$result['code'],
				__( 'Invalid settings payload.', 'site-visibility-manager-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		update_option( AllowlistSettings::OPTION_KEY, $result['settings'], false );

		return new WP_REST_Response( $this->build_payload( $result['settings'] ), 200 );
	}

	/**
	 * @param array<string, mixed> $settings Sanitized settings.
	 * @return array<string, mixed>
	 */
	private function build_payload( array $settings ): array {
		return array(
			'settings'       => $settings,
			'status'         => array(
				'coming_soon' => 'yes' === get_option( 'woocommerce_coming_soon', 'no' ),
				'store_only'  => 'yes' === get_option( 'woocommerce_store_pages_only', 'no' ),
			),
			'links'          => array(
				'site_visibility'      => admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ),
				'coming_soon_template' => admin_url(
					'site-editor.php?postType=wp_template&postId=' . rawurlencode( get_stylesheet() . '//coming-soon' )
				),
				'is_block_theme'       => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			),
			'selected_pages' => $this->titles_for_ids( $settings['page_ids'] ),
			'selected_posts' => $this->titles_for_ids( $settings['post_ids'] ),
			'post_types'     => $this->public_post_types(),
			'taxonomies'     => $this->public_taxonomies(),
			'presets'        => $this->preset_catalog(),
		);
	}

	/**
	 * @return list<array{slug:string,label:string,description:string}>
	 */
	private function preset_catalog(): array {
		return array(
			array(
				'slug'        => 'homepage',
				'label'       => __( 'Homepage', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Front page of the site.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'blog_index',
				'label'       => __( 'Blog index', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Posts page when a static front page is set.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'blog_posts',
				'label'       => __( 'Blog posts', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Individual published posts.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'blog_taxonomies',
				'label'       => __( 'Blog categories & tags', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Category and tag archives.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'shop',
				'label'       => __( 'Shop', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Shop page and product archive.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'products',
				'label'       => __( 'Products', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Individual product pages.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'product_taxonomies',
				'label'       => __( 'Product taxonomies', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Product category and tag archives.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'cart',
				'label'       => __( 'Cart', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Cart page and cart endpoints.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'checkout',
				'label'       => __( 'Checkout', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'Checkout page and checkout endpoints.', 'site-visibility-manager-for-woocommerce' ),
			),
			array(
				'slug'        => 'account',
				'label'       => __( 'My account', 'site-visibility-manager-for-woocommerce' ),
				'description' => __( 'My account page and account endpoints.', 'site-visibility-manager-for-woocommerce' ),
			),
		);
	}

	/**
	 * @param list<int> $ids IDs.
	 * @return list<array{id:int,title:string}>
	 */
	private function titles_for_ids( array $ids ): array {
		$items = array();
		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			if ( '' === $title ) {
				continue;
			}
			$items[] = array(
				'id'    => (int) $id,
				'title' => html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) ),
			);
		}
		return $items;
	}

	/**
	 * @return list<array{name:string,label:string}>
	 */
	private function public_post_types(): array {
		$out   = array();
		$types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $types as $type ) {
			if ( in_array( $type->name, array( 'product', 'attachment' ), true ) ) {
				continue;
			}
			$out[] = array(
				'name'  => $type->name,
				'label' => $type->labels->singular_name,
			);
		}
		return $out;
	}

	/**
	 * @return list<array{name:string,label:string}>
	 */
	private function public_taxonomies(): array {
		$out   = array();
		$taxes = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $taxes as $taxonomy ) {
			if ( 'post_format' === $taxonomy->name ) {
				continue;
			}
			$out[] = array(
				'name'  => $taxonomy->name,
				'label' => $taxonomy->labels->singular_name,
			);
		}
		return $out;
	}
}
