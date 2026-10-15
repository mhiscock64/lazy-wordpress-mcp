<?php
/**
 * Media abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Media abilities.
 */
final class Media_Abilities {

	private const MAX_BASE64_BYTES = 5242880; // 5 MiB decoded.

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_media_list',
			array(
				'label'               => __( 'List media', 'wp-mcp-content' ),
				'description'         => __( 'List media library items.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'    => array( 'type' => 'string' ),
						'mime_type' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
				},
				'execute_callback'    => array( self::class, 'list_media' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_media_get',
			array(
				'label'               => __( 'Get media', 'wp-mcp-content' ),
				'description'         => __( 'Get a media item by ID.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'id' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
				},
				'execute_callback'    => array( self::class, 'get_media' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_media_upload',
			array(
				'label'               => __( 'Upload media', 'wp-mcp-content' ),
				'description'         => __( 'Upload via remote URL sideload or base64 (size-limited). Provide filename plus url or content_base64.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'filename'       => array( 'type' => 'string' ),
						'url'            => array( 'type' => 'string' ),
						'content_base64' => array( 'type' => 'string' ),
						'alt_text'       => array( 'type' => 'string' ),
						'title'          => array( 'type' => 'string' ),
					),
					'required'   => array( 'filename' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'upload_files' );
				},
				'execute_callback'    => array( self::class, 'upload' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_media_update_alt',
			array(
				'label'               => __( 'Update media alt text', 'wp-mcp-content' ),
				'description'         => __( 'Update attachment alt text.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'integer' ),
						'alt_text' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id', 'alt_text' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['id'] ?? 0 );
					return $id && current_user_can( 'edit_post', $id );
				},
				'execute_callback'    => array( self::class, 'update_alt' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
	}

	/**
	 * Format attachment.
	 *
	 * @param \WP_Post $post Attachment.
	 * @return array<string,mixed>
	 */
	private static function format( \WP_Post $post ): array {
		return array(
			'id'        => (int) $post->ID,
			'title'     => get_the_title( $post ),
			'url'       => wp_get_attachment_url( $post->ID ),
			'mime_type' => get_post_mime_type( $post ),
			'alt_text'  => (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			'date'      => $post->post_date_gmt,
		);
	}

	/**
	 * List media.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_media( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) ),
			'paged'          => max( 1, (int) ( $input['page'] ?? 1 ) ),
			's'              => isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '',
		);
		if ( ! empty( $input['mime_type'] ) ) {
			$args['post_mime_type'] = sanitize_mime_type( (string) $input['mime_type'] );
		}
		$q     = new \WP_Query( $args );
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
		);
	}

	/**
	 * Get media.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_media( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post = get_post( (int) $input['id'] );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Media not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = self::format( $post );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Upload media.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function upload( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$filename = sanitize_file_name( (string) $input['filename'] );
		$id       = 0;

		if ( ! empty( $input['url'] ) ) {
			$url = esc_url_raw( (string) $input['url'] );
			$id  = media_sideload_image( $url, 0, null, 'id' );
			if ( is_wp_error( $id ) ) {
				Ability_Registrar::end( $ctx['switched'] );
				return $id;
			}
		} elseif ( ! empty( $input['content_base64'] ) ) {
			$raw = base64_decode( (string) $input['content_base64'], true );
			if ( false === $raw ) {
				Ability_Registrar::end( $ctx['switched'] );
				return new \WP_Error( 'invalid_base64', __( 'Invalid base64 content.', 'wp-mcp-content' ) );
			}
			if ( strlen( $raw ) > self::MAX_BASE64_BYTES ) {
				Ability_Registrar::end( $ctx['switched'] );
				return new \WP_Error( 'too_large', __( 'Decoded file exceeds 5 MiB limit.', 'wp-mcp-content' ) );
			}
			$tmp = wp_tempnam( $filename );
			if ( ! $tmp ) {
				Ability_Registrar::end( $ctx['switched'] );
				return new \WP_Error( 'tmp_failed', __( 'Could not create temp file.', 'wp-mcp-content' ) );
			}
			file_put_contents( $tmp, $raw ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$file_array = array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			);
			$id = media_handle_sideload( $file_array, 0 );
			if ( is_wp_error( $id ) ) {
				@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				Ability_Registrar::end( $ctx['switched'] );
				return $id;
			}
		} else {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'missing_source', __( 'Provide url or content_base64.', 'wp-mcp-content' ) );
		}

		if ( ! empty( $input['title'] ) ) {
			wp_update_post(
				array(
					'ID'         => (int) $id,
					'post_title' => sanitize_text_field( (string) $input['title'] ),
				)
			);
		}
		if ( isset( $input['alt_text'] ) ) {
			update_post_meta( (int) $id, '_wp_attachment_image_alt', sanitize_text_field( (string) $input['alt_text'] ) );
		}

		$post = get_post( (int) $id );
		$result = $post ? self::format( $post ) : array( 'id' => (int) $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Update alt text.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function update_alt( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$id = (int) $input['id'];
		$post = get_post( $id );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Media not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( (string) $input['alt_text'] ) );
		$result = self::format( $post );
		$result['alt_text'] = sanitize_text_field( (string) $input['alt_text'] );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
