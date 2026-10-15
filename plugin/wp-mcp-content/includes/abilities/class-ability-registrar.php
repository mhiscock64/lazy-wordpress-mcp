<?php
/**
 * Registers ability category and all abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Ability registrar.
 */
final class Ability_Registrar {

	public const CATEGORY = 'wp-mcp-content';

	/**
	 * Tool slug => label for settings allow-list.
	 *
	 * @return array<string,string>
	 */
	public static function tool_slugs(): array {
		return array(
			'wp_site_info'                  => 'Site info',
			'wp_site_environment'           => 'Site environment',
			'wp_posts_list'                 => 'List posts',
			'wp_posts_get'                  => 'Get post',
			'wp_posts_create'               => 'Create post',
			'wp_posts_update'               => 'Update post',
			'wp_posts_delete'               => 'Delete post',
			'wp_posts_set_status'           => 'Set post status',
			'wp_pages_list'                 => 'List pages',
			'wp_pages_get'                  => 'Get page',
			'wp_pages_create'               => 'Create page',
			'wp_pages_update'               => 'Update page',
			'wp_pages_delete'               => 'Delete page',
			'wp_pages_set_status'           => 'Set page status',
			'wp_media_list'                 => 'List media',
			'wp_media_get'                  => 'Get media',
			'wp_media_upload'               => 'Upload media',
			'wp_media_update_alt'           => 'Update media alt',
			'wp_taxonomies_list_categories'=> 'List categories',
			'wp_taxonomies_list_tags'       => 'List tags',
			'wp_taxonomies_create_category' => 'Create category',
			'wp_taxonomies_create_tag'      => 'Create tag',
			'wp_taxonomies_assign'          => 'Assign taxonomies',
			'wp_comments_list'              => 'List comments',
			'wp_comments_approve'           => 'Approve comment',
			'wp_comments_reply'             => 'Reply to comment',
			'wp_comments_trash'             => 'Trash/spam comment',
			'wp_users_list'                 => 'List users',
			'wp_users_me'                   => 'Current user',
			'wp_settings_get'               => 'Get settings',
			'wp_settings_update'            => 'Update settings',
			'wp_plugins_themes_list'        => 'List plugins/themes',
			'wp_seo_get'                    => 'Get SEO meta',
			'wp_seo_update'                 => 'Update SEO meta',
			'wp_woo_products_list'          => 'List Woo products',
			'wp_woo_products_get'           => 'Get Woo product',
			'wp_woo_orders_list'            => 'List Woo orders',
			'wp_woo_orders_get'             => 'Get Woo order',
		);
	}

	/**
	 * Ability names exposed as tools on the custom server (filtered by allow-list).
	 *
	 * @return list<string>
	 */
	public static function ability_names_for_server(): array {
		$names = array();
		foreach ( array_keys( self::tool_slugs() ) as $slug ) {
			if ( Settings::is_tool_allowed( $slug ) ) {
				$names[] = self::CATEGORY . '/' . str_replace( '_', '-', $slug );
			}
		}
		return $names;
	}

	/**
	 * Map underscore tool slug to ability name.
	 */
	public static function ability_name( string $tool_slug ): string {
		return self::CATEGORY . '/' . str_replace( '_', '-', $tool_slug );
	}

	/**
	 * Register category.
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'MCP Content', 'wp-mcp-content' ),
				'description' => __( 'Content bridge abilities for MCP clients.', 'wp-mcp-content' ),
			)
		);
	}

	/**
	 * Register all abilities.
	 */
	public static function register_all(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		Site_Abilities::register();
		Post_Abilities::register();
		Page_Abilities::register();
		Media_Abilities::register();
		Taxonomy_Abilities::register();
		Comment_Abilities::register();
		User_Abilities::register();
		Settings_Abilities::register();
		Plugin_Theme_Abilities::register();
		Seo_Abilities::register();
		WooCommerce_Abilities::register();
		Resource_Registrar::register();
		Prompt_Registrar::register();
	}

	/**
	 * Shared meta for public MCP tools.
	 *
	 * @param array<string,mixed> $annotations Annotations.
	 * @return array<string,mixed>
	 */
	public static function tool_meta( array $annotations = array() ): array {
		return array(
			'public'      => true,
			'mcp'         => array(
				'public' => true,
				'type'   => 'tool',
			),
			'annotations' => $annotations,
		);
	}

	/**
	 * Require confirm true.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return true|\WP_Error
	 */
	public static function require_confirm( array $input ) {
		if ( empty( $input['confirm'] ) ) {
			return new \WP_Error(
				'wp_mcp_content_confirm_required',
				__( 'This operation requires confirm: true.', 'wp-mcp-content' ),
				array( 'status' => 400 )
			);
		}
		return true;
	}

	/**
	 * Writes gate.
	 *
	 * @return true|\WP_Error
	 */
	public static function require_writes_enabled() {
		if ( ! Settings::writes_enabled() ) {
			return new \WP_Error(
				'wp_mcp_content_writes_disabled',
				__( 'Write tools are disabled in MCP Content settings.', 'wp-mcp-content' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Pre-execute: rate limit + optional blog switch.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array{switched:bool}| \WP_Error
	 */
	public static function begin( array $input ) {
		$guard = Rate_Limiter::guard();
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$switched = Plugin::maybe_switch_blog( $input );
		return array( 'switched' => $switched );
	}

	/**
	 * End blog switch.
	 */
	public static function end( bool $switched ): void {
		if ( $switched && is_multisite() ) {
			restore_current_blog();
		}
	}

	/**
	 * Register one ability helper.
	 *
	 * @param string               $tool_slug Underscore slug.
	 * @param array<string,mixed>  $args Ability args without name/category/meta merge.
	 */
	public static function register_tool( string $tool_slug, array $args ): void {
		if ( ! Settings::is_tool_allowed( $tool_slug ) ) {
			// Still register but permission will deny if we want discovery consistency;
			// for custom server we filter the list. Registering always keeps schemas available.
		}
		$meta = $args['meta'] ?? self::tool_meta();
		unset( $args['meta'] );
		wp_register_ability(
			self::ability_name( $tool_slug ),
			array_merge(
				array(
					'category' => self::CATEGORY,
					'meta'     => $meta,
				),
				$args
			)
		);
	}
}
