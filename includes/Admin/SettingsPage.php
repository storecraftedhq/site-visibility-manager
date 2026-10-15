<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager\Admin;

/**
 * WooCommerce → Settings tab that mounts a full React settings UI
 * (same pattern as Woo’s Site Visibility: settings tab + React fill).
 */
final class SettingsPage {

	public const TAB_ID  = 'site_visibility_manager';
	public const ROOT_ID = 'svm-settings-root';

	public function register(): void {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 60 );
		add_action( 'woocommerce_settings_' . self::TAB_ID, array( $this, 'render' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * @param array<string, string> $tabs Settings tabs.
	 * @return array<string, string>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[ self::TAB_ID ] = __( 'Site Visibility Manager', 'site-visibility-manager-for-woocommerce' );
		return $tabs;
	}

	public function render(): void {
		printf(
			'<div id="%1$s" class="svm-settings-root"></div>',
			esc_attr( self::ROOT_ID )
		);
	}

	public function enqueue_assets(): void {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		$asset_file = SVM_PLUGIN_DIR . 'assets/admin/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array(),
				'version'      => SVM_VERSION,
			);

		$dependencies = is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();

		wp_enqueue_script(
			'svm-admin',
			SVM_PLUGIN_URL . 'assets/admin/index.js',
			$dependencies,
			$asset['version'],
			true
		);

		wp_enqueue_style( 'wp-components' );

		$built_css = SVM_PLUGIN_DIR . 'assets/admin/style-index.css';
		if ( file_exists( $built_css ) ) {
			wp_enqueue_style(
				'svm-admin',
				SVM_PLUGIN_URL . 'assets/admin/style-index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_localize_script(
			'svm-admin',
			'svmAdmin',
			array(
				'restUrl'   => esc_url_raw( rest_url( 'site-visibility-manager/v1/settings' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'tabId'     => self::TAB_ID,
				'rootId'    => self::ROOT_ID,
			)
		);
	}

	private function should_enqueue(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab check.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return 'wc-settings' === $page && self::TAB_ID === $tab;
	}
}
