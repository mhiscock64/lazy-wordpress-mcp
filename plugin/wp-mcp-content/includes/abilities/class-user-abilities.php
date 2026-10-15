<?php
/**
 * User abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * User abilities.
 */
final class User_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_users_list',
			array(
				'label'               => __( 'List users', 'wp-mcp-content' ),
				'description'         => __( 'List users with limited fields (id, name, slug, roles).', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'   => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'list_users' );
				},
				'execute_callback'    => array( self::class, 'list_users' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_users_me',
			array(
				'label'               => __( 'Current user', 'wp-mcp-content' ),
				'description'         => __( 'Get the authenticated WordPress user.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new \stdClass(),
					'additionalProperties' => false,
				),
				'permission_callback' => static function (): bool {
					return is_user_logged_in();
				},
				'execute_callback'    => array( self::class, 'me' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
	}

	/**
	 * Format user (limited fields).
	 *
	 * @param \WP_User $user User.
	 * @return array<string,mixed>
	 */
	private static function format( \WP_User $user ): array {
		return array(
			'id'           => (int) $user->ID,
			'name'         => $user->display_name,
			'slug'         => $user->user_nicename,
			'roles'        => array_values( $user->roles ),
			'registered'   => $user->user_registered,
		);
	}

	/**
	 * List users.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_users( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$query    = new \WP_User_Query(
			array(
				'number'  => $per_page,
				'paged'   => $page,
				'search'  => isset( $input['search'] ) ? '*' . sanitize_text_field( (string) $input['search'] ) . '*' : '',
				'fields'  => 'all',
			)
		);
		$items = array();
		foreach ( $query->get_results() as $user ) {
			if ( $user instanceof \WP_User ) {
				$items[] = self::format( $user );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array(
			'items' => $items,
			'total' => (int) $query->get_total(),
			'page'  => $page,
		);
	}

	/**
	 * Current user.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function me( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_logged_in', __( 'Not authenticated.', 'wp-mcp-content' ), array( 'status' => 401 ) );
		}
		$result = self::format( $user );
		$result['capabilities_sample'] = array_keys( array_filter( (array) $user->allcaps ) );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
