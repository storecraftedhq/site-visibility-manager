<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class RequestContextFactory {

	public static function from_wp(): RequestContext {
		$post_type             = '';
		$post_status           = '';
		$object_id             = (int) get_queried_object_id();
		$post_type_archive     = '';
		$taxonomy              = '';
		$taxonomy_object_types = array();

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$post_type   = (string) $post->post_type;
				$post_status = (string) $post->post_status;
				$object_id   = (int) $post->ID;
			}
		}

		if ( is_post_type_archive() ) {
			$post_type_archive = (string) get_query_var( 'post_type' );
			if ( is_array( $post_type_archive ) ) {
				$post_type_archive = (string) reset( $post_type_archive );
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$taxonomy = (string) $term->taxonomy;
				$tax_obj  = get_taxonomy( $taxonomy );
				if ( $tax_obj instanceof \WP_Taxonomy ) {
					$taxonomy_object_types = array_values( array_map( 'strval', (array) $tax_obj->object_type ) );
				}
			}
		}

		$is_shop     = function_exists( 'is_shop' ) && is_shop();
		$is_cart     = function_exists( 'is_cart' ) && is_cart();
		$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
		$is_account  = function_exists( 'is_account_page' ) && is_account_page();

		return new RequestContext(
			is_feed: is_feed(),
			is_search: is_search(),
			is_attachment: is_attachment(),
			is_author: is_author(),
			is_date: is_date(),
			is_front_page: is_front_page(),
			is_home: is_home(),
			is_singular: is_singular(),
			post_type: $post_type,
			post_status: $post_status,
			queried_object_id: $object_id,
			is_category: is_category(),
			is_tag: is_tag(),
			is_post_type_archive: is_post_type_archive(),
			post_type_archive: $post_type_archive,
			is_tax: is_tax() || is_category() || is_tag(),
			taxonomy: $taxonomy,
			taxonomy_object_types: $taxonomy_object_types,
			is_shop: $is_shop,
			is_cart: $is_cart,
			is_checkout: $is_checkout,
			is_account_page: $is_account,
		);
	}
}
