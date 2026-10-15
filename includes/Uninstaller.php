<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class Uninstaller {

	/**
	 * @param array<string, mixed> $settings Stored settings array.
	 */
	public static function should_delete_data( array $settings ): bool {
		return ! empty( $settings['delete_data_on_uninstall'] );
	}

	public static function run(): void {
		$settings = get_option( AllowlistSettings::OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		if ( ! self::should_delete_data( $settings ) ) {
			return;
		}

		delete_option( AllowlistSettings::OPTION_KEY );
	}
}
