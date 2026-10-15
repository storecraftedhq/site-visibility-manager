<?php
/**
 * SPL autoloader for StoreCrafted\SiteVisibilityManager\ (no Composer runtime vendor).
 *
 * Mirrors the Store Maintenance Checklist pattern: register an SPL loader that
 * maps plugin classes under includes/ only. Composer remains dev-only (PHPUnit /
 * PHPCS) and is never shipped in the WordPress.org zip.
 *
 * @package StoreCrafted\SiteVisibilityManager
 */

declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

/**
 * Maps PSR-4 classes under this namespace to includes/ files.
 */
final class Autoloader {

	private const PREFIX = __NAMESPACE__ . '\\';

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	/**
	 * Load a class file when the class is under this plugin's namespace.
	 *
	 * @param string $class_name Fully-qualified class name.
	 */
	public static function autoload( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		if ( '' === $relative ) {
			return;
		}

		$segments = explode( '\\', $relative );
		foreach ( $segments as $segment ) {
			// Reject empty / traversal segments (STMC constrains names via class-*.php transform).
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return;
			}
			if ( 1 !== preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $segment ) ) {
				return;
			}
		}

		$relative_file = implode( '/', $segments ) . '.php';

		$candidates = array(
			SVM_PLUGIN_DIR . 'includes/' . $relative_file,
		);

		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Candidate path confined under includes/.
				return;
			}
		}
	}
}
