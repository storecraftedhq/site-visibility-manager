<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class Activator {

	public static function activate(): void {
		if ( ! class_exists( \WooCommerce::class ) ) {
			self::bail( __( 'Site Visibility Manager For WooCommerce requires WooCommerce.', 'site-visibility-manager-for-woocommerce' ) );
		}

		if ( ! defined( 'WC_VERSION' ) || version_compare( (string) WC_VERSION, '11.0', '<' ) ) {
			self::bail( __( 'Site Visibility Manager For WooCommerce requires WooCommerce 11.0 or newer.', 'site-visibility-manager-for-woocommerce' ) );
		}
	}

	private static function bail( string $message ): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( SVM_PLUGIN_FILE ) );
		wp_die(
			esc_html( $message ),
			esc_html__( 'Plugin activation error', 'site-visibility-manager-for-woocommerce' ),
			array( 'back_link' => true )
		);
	}
}
