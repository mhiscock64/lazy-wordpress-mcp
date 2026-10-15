<?php
/**
 * Plugin/theme read-only abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Plugin/theme abilities.
 */
final class Plugin_Theme_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_plugins_themes_list',
			array(
				'label'               => __( 'List plugins and theme', 'wp-mcp-content' ),
				'description'         => __( 'List active plugins and the current theme (read-only).', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => array( self::class, 'list_all' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
	}

	/**
	 * List active plugins and theme.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_all( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all      = get_plugins();
		$active   = (array) get_option( 'active_plugins', array() );
		$plugins  = array();
		foreach ( $active as $file ) {
			if ( isset( $all[ $file ] ) ) {
				$plugins[] = array(
					'file'    => $file,
					'name'    => $all[ $file ]['Name'] ?? $file,
					'version' => $all[ $file ]['Version'] ?? '',
				);
			}
		}
		$theme = wp_get_theme();
		$result = array(
			'active_plugins' => $plugins,
			'theme'          => array(
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
				'stylesheet' => $theme->get_stylesheet(),
			),
		);
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
