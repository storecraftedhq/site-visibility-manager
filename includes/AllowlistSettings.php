<?php
declare(strict_types=1);

namespace StoreCrafted\SiteVisibilityManager;

final class AllowlistSettings {

	public const OPTION_KEY = 'site_visibility_manager_settings';

	/**
	 * Known preset slugs (Phase 1 + Phase 2). Unknown values are dropped on sanitize.
	 *
	 * @var list<string>
	 */
	public const PRESETS = array(
		'homepage',
		'blog_index',
		'blog_posts',
		'blog_taxonomies',
		'shop',
		'products',
		'product_taxonomies',
		'cart',
		'checkout',
		'account',
	);

	/**
	 * @return array{
	 *   presets: list<string>,
	 *   page_ids: list<int>,
	 *   post_ids: list<int>,
	 *   post_types: list<string>,
	 *   taxonomy_allowlist: list<string>,
	 *   delete_data_on_uninstall: bool
	 * }
	 */
	public static function defaults(): array {
		return array(
			'presets'                  => array(),
			'page_ids'                 => array(),
			'post_ids'                 => array(),
			'post_types'               => array(),
			'taxonomy_allowlist'       => array(),
			'delete_data_on_uninstall' => false,
		);
	}

	/**
	 * @return array{
	 *   presets: list<string>,
	 *   page_ids: list<int>,
	 *   post_ids: list<int>,
	 *   post_types: list<string>,
	 *   taxonomy_allowlist: list<string>,
	 *   delete_data_on_uninstall: bool
	 * }
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * @param array<string, mixed> $input Raw settings.
	 * @return array{
	 *   presets: list<string>,
	 *   page_ids: list<int>,
	 *   post_ids: list<int>,
	 *   post_types: list<string>,
	 *   taxonomy_allowlist: list<string>,
	 *   delete_data_on_uninstall: bool
	 * }
	 */
	public static function sanitize( array $input ): array {
		$presets = array();
		if ( isset( $input['presets'] ) && is_array( $input['presets'] ) ) {
			foreach ( $input['presets'] as $preset ) {
				if ( ! is_string( $preset ) ) {
					continue;
				}
				if ( in_array( $preset, self::PRESETS, true ) && ! in_array( $preset, $presets, true ) ) {
					$presets[] = $preset;
				}
			}
		}

		return array(
			'presets'                  => $presets,
			'page_ids'                 => self::sanitize_id_list( $input['page_ids'] ?? array() ),
			'post_ids'                 => self::sanitize_id_list( $input['post_ids'] ?? array() ),
			'post_types'               => self::sanitize_post_types( $input['post_types'] ?? array() ),
			'taxonomy_allowlist'       => self::sanitize_taxonomy_allowlist( $input['taxonomy_allowlist'] ?? array() ),
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ),
		);
	}

	/**
	 * @param mixed $value Raw ID list.
	 * @return list<int>
	 */
	private static function sanitize_id_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $id ) {
			if ( is_string( $id ) && ! is_numeric( $id ) ) {
				continue;
			}
			$int = (int) $id;
			if ( $int > 0 && ! in_array( $int, $ids, true ) ) {
				$ids[] = $int;
			}
		}

		return $ids;
	}

	/**
	 * @param mixed $value Raw post type list.
	 * @return list<string>
	 */
	private static function sanitize_post_types( mixed $value ): array {
		$types = self::sanitize_string_list( $value );
		$types = array_values(
			array_filter(
				$types,
				static fn( string $type ): bool => ! in_array( $type, array( 'product', 'attachment' ), true )
			)
		);

		return array_values( array_intersect( $types, self::registered_public_post_type_names() ) );
	}

	/**
	 * @param mixed $value Raw taxonomy list.
	 * @return list<string>
	 */
	private static function sanitize_taxonomy_allowlist( mixed $value ): array {
		$taxes = self::sanitize_string_list( $value );

		return array_values( array_intersect( $taxes, self::registered_public_taxonomy_names() ) );
	}

	/**
	 * @return list<string>
	 */
	private static function registered_public_post_type_names(): array {
		if ( ! function_exists( 'get_post_types' ) ) {
			return array();
		}

		$names = get_post_types( array( 'public' => true ), 'names' );
		if ( ! is_array( $names ) ) {
			return array();
		}

		return array_values( array_map( 'strval', $names ) );
	}

	/**
	 * @return list<string>
	 */
	private static function registered_public_taxonomy_names(): array {
		if ( ! function_exists( 'get_taxonomies' ) ) {
			return array();
		}

		$names = get_taxonomies( array( 'public' => true ), 'names' );
		if ( ! is_array( $names ) ) {
			return array();
		}

		return array_values( array_map( 'strval', $names ) );
	}

	/**
	 * @param mixed $value Raw string list.
	 * @return list<string>
	 */
	private static function sanitize_string_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			if ( ! is_string( $item ) ) {
				continue;
			}
			$item = strtolower( (string) preg_replace( '/[^a-z0-9_\-]/i', '', $item ) );
			if ( '' !== $item && ! in_array( $item, $out, true ) ) {
				$out[] = $item;
			}
		}

		return $out;
	}
}
