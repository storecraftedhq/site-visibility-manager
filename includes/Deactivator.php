<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class Deactivator {

	public static function deactivate(): void {
		// Clear scheduled hooks / transients when the product adds them.
	}
}
