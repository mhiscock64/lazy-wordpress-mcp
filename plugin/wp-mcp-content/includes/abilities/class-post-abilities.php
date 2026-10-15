<?php
/**
 * Post abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Post abilities.
 */
final class Post_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_posts_list',
			array(
				'label'               => __( 'List posts', 'wp-mcp-content' ),
				'description'         => __( 'List posts with pagination and filters.', 'wp-mcp-content' ),
				'input_schema'        => self::list_schema(),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'list_posts' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_posts_get',
			array(
				'label'               => __( 'Get post', 'wp-mcp-content' ),
				'description'         => __( 'Get a single post by ID.', 'wp-mcp-content' ),
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
					return $id && current_user_can( 'read_post', $id );
				},
				'execute_callback'    => array( self::class, 'get_post' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_posts_create',
			array(
				'label'               => __( 'Create post', 'wp-mcp-content' ),
				'description'         => __( 'Create a post (default status draft). Publishing requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => self::create_schema(),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'create_post' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false, 'destructive' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_posts_update',
			array(
				'label'               => __( 'Update post', 'wp-mcp-content' ),
				'description'         => __( 'Update an existing post.', 'wp-mcp-content' ),
				'input_schema'        => self::update_schema(),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					return $id && current_user_can( 'edit_post', $id );
				},
				'execute_callback'    => array( self::class, 'update_post' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_posts_delete',
			array(
				'label'               => __( 'Delete post', 'wp-mcp-content' ),
				'description'         => __( 'Trash or permanently delete a post. Requires confirm: true.', 'wp-mcp-content' ),
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
					return $id && current_user_can( 'delete_post', $id );
				},
				'execute_callback'    => array( self::class, 'delete_post' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true, 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_posts_set_status',
			array(
				'label'               => __( 'Set post status', 'wp-mcp-content' ),
				'description'         => __( 'Change post status. Requires confirm: true (especially for publish).', 'wp-mcp-content' ),
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
					if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
						return false;
					}
					if ( ( $input['status'] ?? '' ) === 'publish' ) {
						return current_user_can( 'publish_posts' );
					}
					return true;
				},
				'execute_callback'    => array( self::class, 'set_status' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true, 'readonly' => false ) ),
			)
		);
	}

	/**
	 * List schema.
	 *
	 * @return array<string,mixed>
	 */
	private static function list_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
				'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
				'search'   => array( 'type' => 'string' ),
				'status'   => array( 'type' => 'string' ),
				'author'   => array( 'type' => 'integer' ),
				'blog_id'  => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Create schema.
	 *
	 * @return array<string,mixed>
	 */
	private static function create_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'title'      => array( 'type' => 'string' ),
				'content'    => array( 'type' => 'string' ),
				'excerpt'    => array( 'type' => 'string' ),
				'status'     => array(
					'type'    => 'string',
					'enum'    => array( 'draft', 'pending', 'private', 'publish' ),
					'default' => 'draft',
				),
				'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'confirm'    => array( 'type' => 'boolean' ),
				'blog_id'    => array( 'type' => 'integer' ),
			),
			'required'   => array( 'title' ),
		);
	}

	/**
	 * Update schema.
	 *
	 * @return array<string,mixed>
	 */
	private static function update_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'         => array( 'type' => 'integer' ),
				'title'      => array( 'type' => 'string' ),
				'content'    => array( 'type' => 'string' ),
				'excerpt'    => array( 'type' => 'string' ),
				'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'blog_id'    => array( 'type' => 'integer' ),
			),
			'required'   => array( 'id' ),
		);
	}

	/**
	 * Format post.
	 *
	 * @param \WP_Post $post Post.
	 * @return array<string,mixed>
	 */
	public static function format( \WP_Post $post ): array {
		return array(
			'id'         => (int) $post->ID,
			'title'      => get_the_title( $post ),
			'content'    => $post->post_content,
			'excerpt'    => $post->post_excerpt,
			'status'     => $post->post_status,
			'type'       => $post->post_type,
			'date'       => $post->post_date_gmt,
			'modified'   => $post->post_modified_gmt,
			'author'     => (int) $post->post_author,
			'link'       => get_permalink( $post ),
			'categories' => wp_get_post_categories( $post->ID ),
			'tags'       => wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) ),
		);
	}

	/**
	 * List posts.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_posts( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$args     = array(
			'post_type'      => 'post',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => $input['status'] ?? array( 'publish', 'draft', 'pending', 'private', 'future' ),
			's'              => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
		);
		if ( ! empty( $input['author'] ) ) {
			$args['author'] = (int) $input['author'];
		}
		$q = new \WP_Query( $args );
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
	 * Get post.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_post( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post = get_post( (int) $input['id'] );
		if ( ! $post || 'post' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = self::format( $post );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Create post.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function create_post( array $input = array() ) {
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
			if ( ! current_user_can( 'publish_posts' ) ) {
				return new \WP_Error( 'forbidden', __( 'You cannot publish posts.', 'wp-mcp-content' ), array( 'status' => 403 ) );
			}
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$postarr = array(
			'post_type'    => 'post',
			'post_title'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'post_content' => wp_kses_post( (string) ( $input['content'] ?? '' ) ),
			'post_excerpt' => sanitize_textarea_field( (string) ( $input['excerpt'] ?? '' ) ),
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		);
		$id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $id ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $id;
		}
		if ( ! empty( $input['categories'] ) && is_array( $input['categories'] ) ) {
			wp_set_post_categories( $id, array_map( 'intval', $input['categories'] ) );
		}
		if ( ! empty( $input['tags'] ) && is_array( $input['tags'] ) ) {
			wp_set_post_tags( $id, array_map( 'intval', $input['tags'] ) );
		}
		$post = get_post( $id );
		$result = $post ? self::format( $post ) : array( 'id' => $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Update post.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function update_post( array $input = array() ) {
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
		if ( ! $existing || 'post' !== $existing->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
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
		$updated = wp_update_post( $postarr, true );
		if ( is_wp_error( $updated ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $updated;
		}
		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			wp_set_post_categories( $id, array_map( 'intval', $input['categories'] ) );
		}
		if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
			wp_set_post_tags( $id, array_map( 'intval', $input['tags'] ) );
		}
		$post = get_post( $id );
		$result = $post ? self::format( $post ) : array( 'id' => $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Delete post.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function delete_post( array $input = array() ) {
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
		if ( ! $post || 'post' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = wp_delete_post( $id, $force );
		Ability_Registrar::end( $ctx['switched'] );
		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Could not delete post.', 'wp-mcp-content' ) );
		}
		return array(
			'id'      => $id,
			'deleted' => true,
			'force'   => $force,
		);
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
		if ( ! $post || 'post' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$updated = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => $status,
			),
			true
		);
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
