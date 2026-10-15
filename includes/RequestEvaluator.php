<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class RequestEvaluator {

	/**
	 * @param array<string, mixed> $settings Sanitized allowlist settings.
	 */
	public function is_public( array $settings, RequestContext $context ): bool {
		if ( $this->is_always_private( $context ) ) {
			return false;
		}

		$presets            = $this->string_list( $settings['presets'] ?? array() );
		$page_ids           = $this->int_list( $settings['page_ids'] ?? array() );
		$post_ids           = $this->int_list( $settings['post_ids'] ?? array() );
		$post_types         = $this->string_list( $settings['post_types'] ?? array() );
		$taxonomy_allowlist = $this->string_list( $settings['taxonomy_allowlist'] ?? array() );

		if ( in_array( 'homepage', $presets, true ) && $context->is_front_page ) {
			return true;
		}

		if ( in_array( 'blog_index', $presets, true ) && $context->is_home && ! $context->is_front_page ) {
			return true;
		}

		if (
			in_array( 'blog_posts', $presets, true )
			&& $this->is_publish_singular( $context, 'post' )
		) {
			return true;
		}

		if ( in_array( 'blog_taxonomies', $presets, true ) && ( $context->is_category || $context->is_tag ) ) {
			return true;
		}

		if (
			in_array( 'shop', $presets, true )
			&& ( $context->is_shop || ( $context->is_post_type_archive && 'product' === $context->post_type_archive ) )
		) {
			return true;
		}

		if (
			in_array( 'products', $presets, true )
			&& $this->is_publish_singular( $context, 'product' )
		) {
			return true;
		}

		if (
			in_array( 'product_taxonomies', $presets, true )
			&& $context->is_tax
			&& in_array( $context->taxonomy, array( 'product_cat', 'product_tag' ), true )
		) {
			return true;
		}

		if ( in_array( 'cart', $presets, true ) && $context->is_cart ) {
			return true;
		}

		if ( in_array( 'checkout', $presets, true ) && $context->is_checkout ) {
			return true;
		}

		if ( in_array( 'account', $presets, true ) && $context->is_account_page ) {
			return true;
		}

		if (
			$context->is_singular
			&& 'page' === $context->post_type
			&& 'publish' === $context->post_status
			&& $context->queried_object_id > 0
			&& in_array( $context->queried_object_id, $page_ids, true )
		) {
			return true;
		}

		if (
			$this->is_publish_singular( $context, 'post' )
			&& $context->queried_object_id > 0
			&& in_array( $context->queried_object_id, $post_ids, true )
		) {
			return true;
		}

		if ( $this->matches_post_types( $context, $post_types ) ) {
			return true;
		}

		if (
			$context->is_tax
			&& '' !== $context->taxonomy
			&& in_array( $context->taxonomy, $taxonomy_allowlist, true )
		) {
			return true;
		}

		return false;
	}

	private function is_always_private( RequestContext $context ): bool {
		return $context->is_feed
			|| $context->is_search
			|| $context->is_attachment
			|| $context->is_author
			|| $context->is_date;
	}

	private function is_publish_singular( RequestContext $context, string $post_type ): bool {
		return $context->is_singular
			&& $post_type === $context->post_type
			&& 'publish' === $context->post_status;
	}

	/**
	 * @param list<string> $post_types Allowlisted post types.
	 */
	private function matches_post_types( RequestContext $context, array $post_types ): bool {
		if ( array() === $post_types ) {
			return false;
		}

		if ( $context->is_singular && 'publish' === $context->post_status && in_array( $context->post_type, $post_types, true ) ) {
			return true;
		}

		if ( $context->is_post_type_archive && in_array( $context->post_type_archive, $post_types, true ) ) {
			return true;
		}

		if ( $context->is_tax && '' !== $context->taxonomy ) {
			foreach ( $post_types as $post_type ) {
				if ( in_array( $post_type, $context->taxonomy_object_types, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param mixed $value Raw list.
	 * @return list<string>
	 */
	private function string_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			if ( is_string( $item ) ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * @param mixed $value Raw list.
	 * @return list<int>
	 */
	private function int_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			$out[] = (int) $item;
		}
		return $out;
	}
}
