<?php
/**
 * PHPUnit bootstrap skeleton.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

// In a full WP test suite, load wordpress-tests-lib here.
// For local smoke checks without WP, ability schema tests stay lightweight.

$plugin_root = dirname( __DIR__ );
if ( is_readable( $plugin_root . '/wp-mcp-content.php' ) ) {
	// Soft marker that plugin path resolves.
	define( 'WP_MCP_CONTENT_TEST_ROOT', $plugin_root );
}
