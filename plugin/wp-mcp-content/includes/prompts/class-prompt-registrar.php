<?php
/**
 * MCP prompts as abilities.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Prompt registrar.
 */
final class Prompt_Registrar {

	/**
	 * Ability names for create_server prompts list.
	 *
	 * @return list<string>
	 */
	public static function ability_names(): array {
		return array(
			Ability_Registrar::CATEGORY . '/prompt-draft-post',
			Ability_Registrar::CATEGORY . '/prompt-moderate-comments',
			Ability_Registrar::CATEGORY . '/prompt-seo-audit',
		);
	}

	/**
	 * Register prompts.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			Ability_Registrar::CATEGORY . '/prompt-draft-post',
			array(
				'label'               => __( 'Draft post prompt', 'wp-mcp-content' ),
				'description'         => __( 'Guide the model to draft a WordPress post using MCP tools.', 'wp-mcp-content' ),
				'category'            => Ability_Registrar::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'topic'  => array( 'type' => 'string', 'description' => 'Post topic' ),
						'audience' => array( 'type' => 'string', 'description' => 'Target audience' ),
					),
					'required'   => array( 'topic' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'draft_post' ),
				'meta'                => array(
					'public' => true,
					'mcp'    => array( 'public' => true, 'type' => 'prompt' ),
					'annotations' => array( 'audience' => array( 'user', 'assistant' ), 'priority' => 0.8 ),
				),
			)
		);

		wp_register_ability(
			Ability_Registrar::CATEGORY . '/prompt-moderate-comments',
			array(
				'label'               => __( 'Moderate comments prompt', 'wp-mcp-content' ),
				'description'         => __( 'Guide moderation of pending comments with confirm on destructive actions.', 'wp-mcp-content' ),
				'category'            => Ability_Registrar::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'focus' => array( 'type' => 'string', 'description' => 'Optional focus e.g. spam vs questions' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'moderate_comments' );
				},
				'execute_callback'    => array( self::class, 'moderate_comments' ),
				'meta'                => array(
					'public' => true,
					'mcp'    => array( 'public' => true, 'type' => 'prompt' ),
				),
			)
		);

		wp_register_ability(
			Ability_Registrar::CATEGORY . '/prompt-seo-audit',
			array(
				'label'               => __( 'SEO audit prompt', 'wp-mcp-content' ),
				'description'         => __( 'Audit and optionally update SEO title/description for a post.', 'wp-mcp-content' ),
				'category'            => Ability_Registrar::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'post_id' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'execute_callback'    => array( self::class, 'seo_audit' ),
				'meta'                => array(
					'public' => true,
					'mcp'    => array( 'public' => true, 'type' => 'prompt' ),
				),
			)
		);
	}

	/**
	 * Draft post prompt messages.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>
	 */
	public static function draft_post( array $input = array() ) {
		$topic = sanitize_text_field( (string) ( $input['topic'] ?? '' ) );
		$audience = sanitize_text_field( (string) ( $input['audience'] ?? 'general readers' ) );
		$text = "Draft a WordPress post about \"{$topic}\" for {$audience}. "
			. 'Use wp_posts_create with status draft (default). '
			. 'Do not publish unless the user explicitly asks and then call wp_posts_set_status with confirm:true. '
			. 'Suggest categories/tags via taxonomy tools when helpful.';
		return array(
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => array( 'type' => 'text', 'text' => $text ),
				),
			),
		);
	}

	/**
	 * Moderate comments prompt.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>
	 */
	public static function moderate_comments( array $input = array() ) {
		$focus = sanitize_text_field( (string) ( $input['focus'] ?? 'pending comments' ) );
		$text  = "Moderate {$focus}. List with wp_comments_list (status=hold). "
			. 'Approve genuine comments with confirm:true. Trash or spam junk with wp_comments_trash and confirm:true. '
			. 'Reply helpfully when appropriate via wp_comments_reply.';
		return array(
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => array( 'type' => 'text', 'text' => $text ),
				),
			),
		);
	}

	/**
	 * SEO audit prompt.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>
	 */
	public static function seo_audit( array $input = array() ) {
		$post_id = (int) ( $input['post_id'] ?? 0 );
		$text    = "Audit SEO for post ID {$post_id}. Fetch the post with wp_posts_get and SEO fields with wp_seo_get. "
			. 'Propose title ≤60 chars and meta description ≤160 chars. Update only if Yoast/Rank Math is available and the user agrees, using wp_seo_update.';
		return array(
			'messages' => array(
				array(
					'role'    => 'user',
					'content' => array( 'type' => 'text', 'text' => $text ),
				),
			),
		);
	}
}
