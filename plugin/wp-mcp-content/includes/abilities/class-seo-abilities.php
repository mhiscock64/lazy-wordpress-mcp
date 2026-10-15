<?php
/**
 * SEO abilities (Yoast / Rank Math).
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * SEO abilities.
 */
final class Seo_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_seo_get',
			array(
				'label'               => __( 'Get SEO meta', 'wp-mcp-content' ),
				'description'         => __( 'Get Yoast or Rank Math title/description for a post if present.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array( 'post_id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'post_id' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['post_id'] ?? 0 );
					return $id && current_user_can( 'edit_post', $id );
				},
				'execute_callback'    => array( self::class, 'get_meta' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_seo_update',
			array(
				'label'               => __( 'Update SEO meta', 'wp-mcp-content' ),
				'description'         => __( 'Update Yoast or Rank Math title/description for a post.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
					),
					'required'   => array( 'post_id' ),
				),
				'permission_callback' => static function ( $input = array() ): bool {
					$id = (int) ( $input['post_id'] ?? 0 );
					return $id && current_user_can( 'edit_post', $id );
				},
				'execute_callback'    => array( self::class, 'update_meta' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
	}

	/**
	 * Detect provider.
	 */
	private static function provider(): string {
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Meta' ) ) {
			return 'yoast';
		}
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return 'rank_math';
		}
		return 'none';
	}

	/**
	 * Get meta.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_meta( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post_id  = (int) $input['post_id'];
		$provider = self::provider();
		if ( 'none' === $provider ) {
			Ability_Registrar::end( $ctx['switched'] );
			return array(
				'provider' => 'none',
				'message'  => __( 'Neither Yoast SEO nor Rank Math is active.', 'wp-mcp-content' ),
				'post_id'  => $post_id,
			);
		}
		if ( 'yoast' === $provider ) {
			$title = (string) get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$desc  = (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		} else {
			$title = (string) get_post_meta( $post_id, 'rank_math_title', true );
			$desc  = (string) get_post_meta( $post_id, 'rank_math_description', true );
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array(
			'provider'    => $provider,
			'post_id'     => $post_id,
			'title'       => $title,
			'description' => $desc,
		);
	}

	/**
	 * Update meta.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function update_meta( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$post_id  = (int) $input['post_id'];
		$provider = self::provider();
		if ( 'none' === $provider ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'seo_unavailable', __( 'Neither Yoast SEO nor Rank Math is active.', 'wp-mcp-content' ) );
		}
		if ( 'yoast' === $provider ) {
			if ( isset( $input['title'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( (string) $input['title'] ) );
			}
			if ( isset( $input['description'] ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( (string) $input['description'] ) );
			}
		} else {
			if ( isset( $input['title'] ) ) {
				update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( (string) $input['title'] ) );
			}
			if ( isset( $input['description'] ) ) {
				update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( (string) $input['description'] ) );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return self::get_meta( array( 'post_id' => $post_id ) );
	}
}
