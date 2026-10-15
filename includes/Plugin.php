<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

use StoreCrafted\SiteVisibilityManager\Abilities\Registrar as AbilitiesRegistrar;
use StoreCrafted\SiteVisibilityManager\Admin\SettingsPage;
use StoreCrafted\SiteVisibilityManager\Rest\SettingsController;

final class Plugin {

	public function boot(): void {
		$this->load_textdomain();

		( new ComingSoonIntegration() )->register();
		( new SettingsController() )->register();
		( new AbilitiesRegistrar() )->register();

		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}
	}

	private function load_textdomain(): void {
		load_plugin_textdomain(
			SVM_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( SVM_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
