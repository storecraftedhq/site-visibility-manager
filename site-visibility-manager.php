<?php
/**
 * Plugin Name:       Site Visibility Manager For WooCommerce
 * Plugin URI:        https://storecrafted.com
 * Description:       Granular public allowlist controls for WooCommerce Coming Soon / Site Visibility.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            StoreCrafted
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       site-visibility-manager-for-woocommerce
 * Domain Path:       /languages
 *
 * WC requires at least: 11.0
 * WC tested up to: 11.2
 *
 * @package StoreCrafted\SiteVisibilityManager
 */

declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SVM_VERSION', '1.0.0' );
define( 'SVM_PLUGIN_FILE', __FILE__ );
define( 'SVM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SVM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SVM_TEXT_DOMAIN', 'site-visibility-manager-for-woocommerce' );

require_once SVM_PLUGIN_DIR . 'includes/Autoloader.php';
Autoloader::register();

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( \WooCommerce::class ) || ! defined( 'WC_VERSION' ) || version_compare( (string) WC_VERSION, '11.0', '<' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="error"><p>';
					esc_html_e( 'Site Visibility Manager For WooCommerce requires WooCommerce 11.0 or newer.', 'site-visibility-manager-for-woocommerce' );
					echo '</p></div>';
				},
				10,
				0
			);
			return;
		}

		( new Plugin() )->boot();
	},
	20,
	0
);

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				SVM_PLUGIN_FILE,
				true
			);
		}
	}
);
