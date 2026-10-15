<?php
/**
 * Taxonomy abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Taxonomy abilities.
 */
final class Taxonomy_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_taxonomies_list_categories',
			array(
				'label'               => __( 'List categories', 'wp-mcp-content' ),
				'description'         => __( 'List post categories.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'search'     => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'list_categories' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_taxonomies_list_tags',
			array(
				'label'               => __( 'List tags', 'wp-mcp-content' ),
				'description'         => __( 'List post tags.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array( 'search' => array( 'type' => 'string' ) ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'list_tags' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_taxonomies_create_category',
			array(
				'label'               => __( 'Create category', 'wp-mcp-content' ),
				'description'         => __( 'Create a category.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'name' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_categories' );
				},
				'execute_callback'    => array( self::class, 'create_category' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_taxonomies_create_tag',
			array(
				'label'               => __( 'Create tag', 'wp-mcp-content' ),
				'description'         => __( 'Create a tag.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'name' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'create_tag' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_taxonomies_assign',
			array(
				'label'               => __( 'Assign taxonomies', 'wp-mcp-content' ),
				'description'         => __( 'Assign categories and/or tags to a post.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array( 'type' => 'integer' ),
						'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
						'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					),
					'required'   => array( 'post_id' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['post_id'] ?? 0 );
					return $id && current_user_can( 'edit_post', $id );
				},
				'execute_callback'    => array( self::class, 'assign' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
	}

	/**
	 * Format term.
	 *
	 * @param \WP_Term $term Term.
	 * @return array<string,mixed>
	 */
	private static function format_term( \WP_Term $term ): array {
		return array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => (int) $term->parent,
			'count'       => (int) $term->count,
			'taxonomy'    => $term->taxonomy,
		);
	}

	/**
	 * List categories.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_categories( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$args = array(
			'taxonomy'   => 'category',
			'hide_empty' => ! empty( $input['hide_empty'] ),
			'search'     => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
		);
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $terms;
		}
		$items = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$items[] = self::format_term( $term );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'items' => $items );
	}

	/**
	 * List tags.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_tags( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => false,
				'search'     => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
			)
		);
		if ( is_wp_error( $terms ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $terms;
		}
		$items = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$items[] = self::format_term( $term );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'items' => $items );
	}

	/**
	 * Create category.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function create_category( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$result = wp_insert_term(
			sanitize_text_field( (string) $input['name'] ),
			'category',
			array(
				'parent'      => (int) ( $input['parent'] ?? 0 ),
				'description' => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
			)
		);
		if ( is_wp_error( $result ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $result;
		}
		$term = get_term( (int) $result['term_id'], 'category' );
		$out  = ( $term instanceof \WP_Term ) ? self::format_term( $term ) : array( 'id' => (int) $result['term_id'] );
		Ability_Registrar::end( $ctx['switched'] );
		return $out;
	}

	/**
	 * Create tag.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function create_tag( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$result = wp_insert_term(
			sanitize_text_field( (string) $input['name'] ),
			'post_tag',
			array(
				'description' => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
			)
		);
		if ( is_wp_error( $result ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return $result;
		}
		$term = get_term( (int) $result['term_id'], 'post_tag' );
		$out  = ( $term instanceof \WP_Term ) ? self::format_term( $term ) : array( 'id' => (int) $result['term_id'] );
		Ability_Registrar::end( $ctx['switched'] );
		return $out;
	}

	/**
	 * Assign taxonomies.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function assign( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Post not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			wp_set_post_categories( $post_id, array_map( 'intval', $input['categories'] ) );
		}
		if ( isset( $input['tags'] ) && is_array( $input['tags'] ) ) {
			wp_set_post_tags( $post_id, array_map( 'intval', $input['tags'] ) );
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array(
			'post_id'    => $post_id,
			'categories' => wp_get_post_categories( $post_id ),
			'tags'       => wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) ),
		);
	}
}
