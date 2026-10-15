<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

/**
 * Snapshot of the current front-end request for allowlist evaluation.
 */
final class RequestContext {

	/**
	 * @param list<string> $taxonomy_object_types Post types associated with the current taxonomy archive.
	 */
	public function __construct(
		public readonly bool $is_feed = false,
		public readonly bool $is_search = false,
		public readonly bool $is_attachment = false,
		public readonly bool $is_author = false,
		public readonly bool $is_date = false,
		public readonly bool $is_front_page = false,
		public readonly bool $is_home = false,
		public readonly bool $is_singular = false,
		public readonly string $post_type = '',
		public readonly string $post_status = '',
		public readonly int $queried_object_id = 0,
		public readonly bool $is_category = false,
		public readonly bool $is_tag = false,
		public readonly bool $is_post_type_archive = false,
		public readonly string $post_type_archive = '',
		public readonly bool $is_tax = false,
		public readonly string $taxonomy = '',
		public readonly array $taxonomy_object_types = array(),
		public readonly bool $is_shop = false,
		public readonly bool $is_cart = false,
		public readonly bool $is_checkout = false,
		public readonly bool $is_account_page = false,
	) {
	}
}
