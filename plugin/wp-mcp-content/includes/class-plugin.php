<?php
/**
 * Main plugin bootstrap.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Plugin singleton.
 */
final class Plugin {

	/**
	 * Instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook registrations.
	 */
	public function init(): void {
		Settings::init();
		add_action( 'admin_notices', array( $this, 'maybe_missing_adapter_notice' ) );
		add_action( 'wp_abilities_api_categories_init', array( Ability_Registrar::class, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( Ability_Registrar::class, 'register_all' ) );
		add_action( 'mcp_adapter_init', array( Mcp_Server::class, 'register' ) );
	}

	/**
	 * Admin notice when MCP Adapter is missing.
	 */
	public function maybe_missing_adapter_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( Mcp_Server::adapter_available() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__(
			'WordPress MCP Content Bridge requires the official MCP Adapter (wordpress/mcp-adapter). Install it as a plugin, or run composer require wordpress/mcp-adapter in this plugin directory and ensure vendor/autoload.php loads.',
			'wp-mcp-content'
		);
		echo '</p></div>';
	}

	/**
	 * Optionally switch blog on multisite.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return bool True if switched.
	 */
	public static function maybe_switch_blog( array $input ): bool {
		if ( empty( $input['blog_id'] ) || ! is_multisite() ) {
			return false;
		}
		$blog_id = (int) $input['blog_id'];
		if ( $blog_id <= 0 || get_current_blog_id() === $blog_id ) {
			return false;
		}
		$user_id = get_current_user_id();
		$can = current_user_can( 'manage_sites' );
		if ( ! $can && function_exists( 'current_user_can_for_site' ) ) {
			$can = current_user_can_for_site( $blog_id, 'manage_options' );
		}
		if ( ! $can && ! is_user_member_of_blog( $user_id, $blog_id ) ) {
			return false;
		}
		// Members still need edit caps on the destination site after switch.
		switch_to_blog( $blog_id );
		return true;
	}
}
