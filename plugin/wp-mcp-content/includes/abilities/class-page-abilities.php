<?php
/**
 * Page abilities (mirrors posts for post_type=page).
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Page abilities.
 */
final class Page_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_pages_list',
			array(
				'label'               => __( 'List pages', 'wp-mcp-content' ),
				'description'         => __( 'List pages with pagination and filters.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'   => array( 'type' => 'string' ),
						'status'   => array( 'type' => 'string' ),
						'blog_id'  => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
				'execute_callback'    => array( self::class, 'list_pages' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_pages_get',
			array(
				'label'               => __( 'Get page', 'wp-mcp-content' ),
				'description'         => __( 'Get a single page by ID.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'blog_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					return $id && current_user_can( 'read_page', $id );
				},
				'execute_callback'    => array( self::class, 'get_page' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_pages_create',
			array(
				'label'               => __( 'Create page', 'wp-mcp-content' ),
				'description'         => __( 'Create a page (default draft). Publishing requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'excerpt' => array( 'type' => 'string' ),
						'status'  => array(
							'type'    => 'string',
							'enum'    => array( 'draft', 'pending', 'private', 'publish' ),
							'default' => 'draft',
						),
						'parent'  => array( 'type' => 'integer' ),
						'confirm' => array( 'type' => 'boolean' ),
						'blog_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'title' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_pages' );
				},
				'execute_callback'    => array( self::class, 'create_page' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_pages_update',
			array(
				'label'               => __( 'Update page', 'wp-mcp-content' ),
				'description'         => __( 'Update an existing page.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
						'excerpt' => array( 'type' => 'string' ),
						'parent'  => array( 'type' => 'integer' ),
						'blog_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					return $id && current_user_can( 'edit_page', $id );
				},
				'execute_callback'    => array( self::class, 'update_page' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_pages_delete',
			array(
				'label'               => __( 'Delete page', 'wp-mcp-content' ),
				'description'         => __( 'Trash or permanently delete a page. Requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'force'   => array( 'type' => 'boolean', 'default' => false ),
						'confirm' => array( 'type' => 'boolean' ),
						'blog_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id', 'confirm' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					return $id && current_user_can( 'delete_page', $id );
				},
				'execute_callback'    => array( self::class, 'delete_page' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_pages_set_status',
			array(
				'label'               => __( 'Set page status', 'wp-mcp-content' ),
				'description'         => __( 'Change page status. Requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'status'  => array(
							'type' => 'string',
							'enum' => array( 'draft', 'pending', 'private', 'publish', 'trash' ),
						),
						'confirm' => array( 'type' => 'boolean' ),
						'blog_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id', 'status', 'confirm' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					if ( ! $id || ! current_user_can( 'edit_page', $id ) ) {
						return false;
					}
					if ( ( $input['status'] ?? '' ) === 'publish' ) {
						return current_user_can( 'publish_pages' );
					}
					return true;
				},
				'execute_callback'    => array( self::class, 'set_status' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true ) ),
			)
		);
	}

	/**
	 * Format page.
	 *
	 * @param \WP_Post $post Page.
	 * @return array<string,mixed>
	 */
	private static function format( \WP_Post $post ): array {
		$data           = Post_Abilities::format( $post );
		$data['parent'] = (int) $post->post_parent;
		return $data;
	}

	/**
	 * List pages.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_pages( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$q        = new \WP_Query(
			array(
				'post_type'      => 'page',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'post_status'    => $input['status'] ?? array( 'publish', 'draft', 'pending', 'private' ),
				's'              => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
			)
		);
		$items = array();
		foreach ( $q->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$items[] = self::format( $post );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array(
			'items'       => $items,
			'total'       => (int) $q->found_posts,
			'total_pages' => (int) $q->max_num_pages,
			'page'        => $page,
		);
	}

	/**
	 * Get page.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_page( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post = get_post( (int) $input['id'] );
		if ( ! $post || 'page' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Page not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = self::format( $post );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Create page.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function create_page( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$status = sanitize_key( (string) ( $input['status'] ?? 'draft' ) );
		if ( 'publish' === $status ) {
			$confirm = Ability_Registrar::require_confirm( $input );
			if ( is_wp_error( $confirm ) ) {
				return $confirm;
			}
			if ( ! current_user_can( 'publish_pages' ) ) {
				return new \WP_Error( 'forbidden', __( 'You cannot publish pages.', 'wp-mcp-content' ), array( 'status' => 403 ) );
			}
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
				'post_content' => wp_kses_post( (string) ( $input['content'] ?? '' ) ),
				'post_excerpt' => sanitize_textarea_field( (string) ( $input['excerpt'] ?? '' ) ),
				'post_status'  => $status,
				'post_parent'  => (int) ( $input['parent'] ?? 0 ),
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $id;
		}
		$post = get_post( $id );
		$result = $post ? self::format( $post ) : array( 'id' => $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Update page.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function update_page( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$id = (int) $input['id'];
		$existing = get_post( $id );
		if ( ! $existing || 'page' !== $existing->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Page not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$postarr = array( 'ID' => $id );
		if ( isset( $input['title'] ) ) {
			$postarr['post_title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( isset( $input['content'] ) ) {
			$postarr['post_content'] = wp_kses_post( (string) $input['content'] );
		}
		if ( isset( $input['excerpt'] ) ) {
			$postarr['post_excerpt'] = sanitize_textarea_field( (string) $input['excerpt'] );
		}
		if ( isset( $input['parent'] ) ) {
			$postarr['post_parent'] = (int) $input['parent'];
		}
		$updated = wp_update_post( $postarr, true );
		if ( is_wp_error( $updated ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $updated;
		}
		$post = get_post( $id );
		$result = $post ? self::format( $post ) : array( 'id' => $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Delete page.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function delete_page( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$confirm = Ability_Registrar::require_confirm( $input );
		if ( is_wp_error( $confirm ) ) {
			return $confirm;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$id    = (int) $input['id'];
		$force = ! empty( $input['force'] );
		$post  = get_post( $id );
		if ( ! $post || 'page' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Page not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = wp_delete_post( $id, $force );
		Ability_Registrar::end( $ctx['switched'] );
		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Could not delete page.', 'wp-mcp-content' ) );
		}
		return array( 'id' => $id, 'deleted' => true, 'force' => $force );
	}

	/**
	 * Set status.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function set_status( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$confirm = Ability_Registrar::require_confirm( $input );
		if ( is_wp_error( $confirm ) ) {
			return $confirm;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$id     = (int) $input['id'];
		$status = sanitize_key( (string) $input['status'] );
		$post   = get_post( $id );
		if ( ! $post || 'page' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Page not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$updated = wp_update_post( array( 'ID' => $id, 'post_status' => $status ), true );
		if ( is_wp_error( $updated ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $updated;
		}
		$fresh = get_post( $id );
		$result = $fresh ? self::format( $fresh ) : array( 'id' => $id, 'status' => $status );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
