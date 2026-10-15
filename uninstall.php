<?php
/**
 * Uninstall Site Visibility Manager For WooCommerce.
 *
 * @package StoreCrafted\SiteVisibilityManager
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

define( 'SVM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once SVM_PLUGIN_DIR . 'includes/Autoloader.php';
\StoreCrafted\SiteVisibilityManager\Autoloader::register();

if ( class_exists( \StoreCrafted\SiteVisibilityManager\Uninstaller::class ) ) {
	\StoreCrafted\SiteVisibilityManager\Uninstaller::run();
}
