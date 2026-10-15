<?php
/**
 * Plugin Name:       WordPress MCP Content Bridge
 * Plugin URI:        https://github.com/wordpress-mcp/wp-mcp-content
 * Description:       Expose WordPress content operations as MCP tools/resources/prompts via Abilities API + MCP Adapter.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            WordPress MCP
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-mcp-content
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_MCP_CONTENT_VERSION', '1.0.0' );
define( 'WP_MCP_CONTENT_FILE', __FILE__ );
define( 'WP_MCP_CONTENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_MCP_CONTENT_URL', plugin_dir_url( __FILE__ ) );

$wp_mcp_content_autoload = WP_MCP_CONTENT_DIR . 'vendor/autoload.php';
if ( is_readable( $wp_mcp_content_autoload ) ) {
	require_once $wp_mcp_content_autoload;
}

require_once WP_MCP_CONTENT_DIR . 'includes/class-plugin.php';
require_once WP_MCP_CONTENT_DIR . 'includes/class-rate-limiter.php';
require_once WP_MCP_CONTENT_DIR . 'includes/class-settings.php';
require_once WP_MCP_CONTENT_DIR . 'includes/class-mcp-server.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-ability-registrar.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-site-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-post-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-page-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-media-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-taxonomy-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-comment-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-user-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-settings-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-plugin-theme-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-seo-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/abilities/class-woocommerce-abilities.php';
require_once WP_MCP_CONTENT_DIR . 'includes/resources/class-resource-registrar.php';
require_once WP_MCP_CONTENT_DIR . 'includes/prompts/class-prompt-registrar.php';

/**
 * Bootstrap.
 */
function wp_mcp_content_bootstrap(): void {
	\WP_MCP_Content\Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'wp_mcp_content_bootstrap' );
