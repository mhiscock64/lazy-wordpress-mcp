<?php
/**
 * MCP resources as abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Resource registrar.
 */
final class Resource_Registrar {

	/**
	 * Ability names for create_server resources list.
	 *
	 * @return list<string>
	 */
	public static function ability_names(): array {
		return array(
			Ability_Registrar::CATEGORY . '/resource-recent-posts',
			Ability_Registrar::CATEGORY . '/resource-site-health-summary',
		);
	}

	/**
	 * Register resource abilities.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			Ability_Registrar::CATEGORY . '/resource-recent-posts',
			array(
				'label'               => __( 'Recent posts resource', 'wp-mcp-content' ),
				'description'         => __( 'JSON summary of the 10 most recent posts.', 'wp-mcp-content' ),
				'category'            => Ability_Registrar::CATEGORY,
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'recent_posts' ),
				'meta'                => array(
					'public' => true,
					'uri'    => 'wordpress://wp-mcp-content/recent-posts',
					'mcp'    => array(
						'public' => true,
						'type'   => 'resource',
					),
					'annotations' => array(
						'audience' => array( 'assistant' ),
						'priority' => 0.7,
					),
				),
			)
		);

		wp_register_ability(
			Ability_Registrar::CATEGORY . '/resource-site-health-summary',
			array(
				'label'               => __( 'Site health summary resource', 'wp-mcp-content' ),
				'description'         => __( 'Lightweight site health / environment summary for agents.', 'wp-mcp-content' ),
				'category'            => Ability_Registrar::CATEGORY,
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => array( self::class, 'site_health' ),
				'meta'                => array(
					'public' => true,
					'uri'    => 'wordpress://wp-mcp-content/site-health-summary',
					'mcp'    => array(
						'public' => true,
						'type'   => 'resource',
					),
					'annotations' => array(
						'audience' => array( 'assistant' ),
						'priority' => 0.6,
					),
				),
			)
		);
	}

	/**
	 * Recent posts resource body.
	 *
	 * @return array<string,mixed>
	 */
	public static function recent_posts() {
		$q = new \WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 10,
				'post_status'    => 'publish',
			)
		);
		$items = array();
		foreach ( $q->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$items[] = array(
					'id'    => (int) $post->ID,
					'title' => get_the_title( $post ),
					'link'  => get_permalink( $post ),
					'date'  => $post->post_date_gmt,
				);
			}
		}
		return array(
			'contents' => array(
				array(
					'uri'      => 'wordpress://wp-mcp-content/recent-posts',
					'mimeType' => 'application/json',
					'text'     => wp_json_encode( array( 'items' => $items ) ),
				),
			),
		);
	}

	/**
	 * Site health summary.
	 *
	 * @return array<string,mixed>
	 */
	public static function site_health() {
		$summary = array(
			'wp_version'  => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION,
			'multisite'   => is_multisite(),
			'is_ssl'      => is_ssl(),
			'theme'       => wp_get_theme()->get( 'Name' ),
			'active_plugins_count' => count( (array) get_option( 'active_plugins', array() ) ),
		);
		return array(
			'contents' => array(
				array(
					'uri'      => 'wordpress://wp-mcp-content/site-health-summary',
					'mimeType' => 'application/json',
					'text'     => wp_json_encode( $summary ),
				),
			),
		);
	}
}
