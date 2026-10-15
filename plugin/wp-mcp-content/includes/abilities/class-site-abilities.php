<?php
/**
 * Site abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Site abilities.
 */
final class Site_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_site_info',
			array(
				'label'               => __( 'Get site info', 'wp-mcp-content' ),
				'description'         => __( 'Returns site name, URL, description, and locale.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'blog_id' => array( 'type' => 'integer', 'description' => 'Multisite blog id' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'url'         => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'locale'      => array( 'type' => 'string' ),
						'home'        => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'execute_callback'    => array( self::class, 'get_site_info' ),
				'meta'                => Ability_Registrar::tool_meta(
					array(
						'readonly'  => true,
						'idempotent'=> true,
					)
				),
			)
		);

		Ability_Registrar::register_tool(
			'wp_site_environment',
			array(
				'label'               => __( 'Get environment info', 'wp-mcp-content' ),
				'description'         => __( 'Returns WordPress/PHP versions and environment flags (sanitized).', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' ) || current_user_can( 'read' );
				},
				'execute_callback'    => array( self::class, 'get_environment' ),
				'meta'                => Ability_Registrar::tool_meta(
					array(
						'readonly'   => true,
						'idempotent' => true,
					)
				),
			)
		);
	}

	/**
	 * Site info.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_site_info( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$result = array(
			'name'        => get_bloginfo( 'name' ),
			'url'         => get_site_url(),
			'home'        => get_home_url(),
			'description' => get_bloginfo( 'description' ),
			'locale'      => get_locale(),
		);
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Environment.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_environment( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		global $wpdb;
		$result = array(
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => PHP_VERSION,
			'multisite'    => is_multisite(),
			'environment'  => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			'db_charset'   => $wpdb->charset ?? '',
			'is_ssl'       => is_ssl(),
		);
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
