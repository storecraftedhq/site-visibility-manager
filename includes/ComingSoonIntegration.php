<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class ComingSoonIntegration {

	public function __construct(
		private readonly RequestEvaluator $evaluator = new RequestEvaluator(),
	) {
	}

	public function register(): void {
		add_filter( 'woocommerce_coming_soon_exclude', array( $this, 'exclude_public_requests' ), 10, 1 );
	}

	public function exclude_public_requests( bool $excluded ): bool {
		if ( $excluded ) {
			return true;
		}

		$settings = self::normalize_filter_settings(
			apply_filters( 'svm_allowlist_settings', AllowlistSettings::get() )
		);

		$is_public = $this->should_exclude( false, $settings, RequestContextFactory::from_wp() );

		/**
		 * Filters whether the current request is treated as publicly allowlisted.
		 *
		 * @param bool               $is_public Whether the request is public.
		 * @param array<string,mixed> $settings  Resolved allowlist settings.
		 */
		return (bool) apply_filters( 'svm_is_request_public', $is_public, $settings );
	}

	/**
	 * Normalize `svm_allowlist_settings` filter output before evaluation.
	 *
	 * Non-arrays fall back to defaults; arrays are re-sanitized so companion
	 * plugins cannot widen the allowlist with unexpected shapes.
	 *
	 * @param mixed $settings Raw filter result.
	 * @return array{
	 *   presets: list<string>,
	 *   page_ids: list<int>,
	 *   post_ids: list<int>,
	 *   post_types: list<string>,
	 *   taxonomy_allowlist: list<string>,
	 *   delete_data_on_uninstall: bool
	 * }
	 */
	public static function normalize_filter_settings( mixed $settings ): array {
		if ( ! is_array( $settings ) ) {
			return AllowlistSettings::defaults();
		}

		return AllowlistSettings::sanitize( $settings );
	}

	/**
	 * Pure decision helper (unit-testable without WordPress).
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 */
	public function should_exclude( bool $already_excluded, array $settings, RequestContext $context ): bool {
		if ( $already_excluded ) {
			return true;
		}

		return $this->evaluator->is_public( $settings, $context );
	}
}
