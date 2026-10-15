<?php
/**
 * Settings abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Settings abilities.
 */
final class Settings_Abilities {

	/** @var list<string> */
	private const ALLOWED_KEYS = array(
		'blogname',
		'blogdescription',
		'timezone_string',
		'date_format',
		'time_format',
	);

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_settings_get',
			array(
				'label'               => __( 'Get settings', 'wp-mcp-content' ),
				'description'         => __( 'Read selected site settings only.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => array( self::class, 'get_settings' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_settings_update',
			array(
				'label'               => __( 'Update settings', 'wp-mcp-content' ),
				'description'         => __( 'Update selected settings. Disabled by default; requires settings write toggle, manage_options, and confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'blogname'         => array( 'type' => 'string' ),
						'blogdescription'  => array( 'type' => 'string' ),
						'timezone_string'  => array( 'type' => 'string' ),
						'date_format'      => array( 'type' => 'string' ),
						'time_format'      => array( 'type' => 'string' ),
						'confirm'          => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'confirm' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' ) && Settings::settings_write_enabled();
				},
				'execute_callback'    => array( self::class, 'update_settings' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true, 'readonly' => false ) ),
			)
		);
	}

	/**
	 * Get settings.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_settings( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$out = array();
		foreach ( self::ALLOWED_KEYS as $key ) {
			$out[ $key ] = get_option( $key );
		}
		Ability_Registrar::end( $ctx['switched'] );
		return $out;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function update_settings( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		if ( ! Settings::settings_write_enabled() ) {
			return new \WP_Error( 'settings_write_disabled', __( 'Settings write is disabled.', 'wp-mcp-content' ), array( 'status' => 403 ) );
		}
		$confirm = Ability_Registrar::require_confirm( $input );
		if ( is_wp_error( $confirm ) ) {
			return $confirm;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$updated = array();
		foreach ( self::ALLOWED_KEYS as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$value = sanitize_text_field( (string) $input[ $key ] );
				update_option( $key, $value );
				$updated[ $key ] = $value;
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'updated' => $updated );
	}
}
