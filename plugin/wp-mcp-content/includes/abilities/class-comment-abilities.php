<?php
/**
 * Comment abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Comment abilities.
 */
final class Comment_Abilities {

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_comments_list',
			array(
				'label'               => __( 'List comments', 'wp-mcp-content' ),
				'description'         => __( 'List comments with filters.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'status'   => array( 'type' => 'string' ),
						'post'     => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'execute_callback'    => array( self::class, 'list_comments' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_comments_approve',
			array(
				'label'               => __( 'Approve comment', 'wp-mcp-content' ),
				'description'         => __( 'Approve a comment. Requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'confirm' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'id', 'confirm' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'execute_callback'    => array( self::class, 'approve' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_comments_reply',
			array(
				'label'               => __( 'Reply to comment', 'wp-mcp-content' ),
				'description'         => __( 'Reply to a comment as the current user.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'parent'  => array( 'type' => 'integer' ),
						'post'    => array( 'type' => 'integer' ),
						'content' => array( 'type' => 'string' ),
					),
					'required'   => array( 'parent', 'post', 'content' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'execute_callback'    => array( self::class, 'reply' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => false ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_comments_trash',
			array(
				'label'               => __( 'Trash or spam comment', 'wp-mcp-content' ),
				'description'         => __( 'Trash a comment or mark as spam. Requires confirm: true.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'as_spam' => array( 'type' => 'boolean', 'default' => false ),
						'confirm' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'id', 'confirm' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'execute_callback'    => array( self::class, 'trash' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'destructive' => true ) ),
			)
		);
	}

	/**
	 * Format comment.
	 *
	 * @param \WP_Comment $comment Comment.
	 * @return array<string,mixed>
	 */
	private static function format( \WP_Comment $comment ): array {
		return array(
			'id'           => (int) $comment->comment_ID,
			'post'         => (int) $comment->comment_post_ID,
			'author_name'  => $comment->comment_author,
			'author_email' => $comment->comment_author_email,
			'content'      => $comment->comment_content,
			'status'       => wp_get_comment_status( $comment ),
			'parent'       => (int) $comment->comment_parent,
			'date'         => $comment->comment_date_gmt,
		);
	}

	/**
	 * List comments.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_comments( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$args     = array(
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'status'  => $input['status'] ?? 'hold',
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		);
		if ( ! empty( $input['post'] ) ) {
			$args['post_id'] = (int) $input['post'];
		}
		$comments = get_comments( $args );
		$items    = array();
		foreach ( $comments as $comment ) {
			if ( $comment instanceof \WP_Comment ) {
				$items[] = self::format( $comment );
			}
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'items' => $items, 'page' => $page );
	}

	/**
	 * Approve.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function approve( array $input = array() ) {
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
		$id = (int) $input['id'];
		$r  = wp_set_comment_status( $id, 'approve' );
		if ( is_wp_error( $r ) || ! $r ) {
			Ability_Registrar::end( $ctx['switched'] );
			return is_wp_error( $r ) ? $r : new \WP_Error( 'approve_failed', __( 'Could not approve comment.', 'wp-mcp-content' ) );
		}
		$comment = get_comment( $id );
		$result  = ( $comment instanceof \WP_Comment ) ? self::format( $comment ) : array( 'id' => $id, 'status' => 'approved' );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Reply.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function reply( array $input = array() ) {
		$writes = Ability_Registrar::require_writes_enabled();
		if ( is_wp_error( $writes ) ) {
			return $writes;
		}
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		$user = wp_get_current_user();
		$data = array(
			'comment_post_ID'      => (int) $input['post'],
			'comment_parent'       => (int) $input['parent'],
			'comment_content'      => wp_kses_post( (string) $input['content'] ),
			'user_id'              => $user->ID,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_author_url'   => $user->user_url,
			'comment_approved'     => 1,
		);
		$id = wp_insert_comment( $data );
		if ( ! $id ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'reply_failed', __( 'Could not create reply.', 'wp-mcp-content' ) );
		}
		$comment = get_comment( $id );
		$result  = ( $comment instanceof \WP_Comment ) ? self::format( $comment ) : array( 'id' => $id );
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * Trash/spam.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function trash( array $input = array() ) {
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
		$id = (int) $input['id'];
		if ( ! empty( $input['as_spam'] ) ) {
			$ok = wp_spam_comment( $id );
			$action = 'spam';
		} else {
			$ok = wp_trash_comment( $id );
			$action = 'trash';
		}
		Ability_Registrar::end( $ctx['switched'] );
		if ( ! $ok ) {
			return new \WP_Error( 'moderation_failed', __( 'Could not moderate comment.', 'wp-mcp-content' ) );
		}
		return array( 'id' => $id, 'action' => $action, 'ok' => true );
	}
}
