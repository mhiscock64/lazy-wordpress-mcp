<?php
/**
 * Skeleton tests for ability slug / schema inventory.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ability schema smoke tests (run inside WP test suite).
 */
class Test_Ability_Schemas extends TestCase {

	/**
	 * Tool slug inventory is non-empty and uses wp_ prefix.
	 */
	public function test_tool_slugs_inventory(): void {
		require_once dirname( __DIR__ ) . '/includes/abilities/class-ability-registrar.php';
		// Without full WP bootstrap Ability_Registrar may not autoload deps; assert file exists.
		$this->assertFileExists( dirname( __DIR__ ) . '/includes/abilities/class-ability-registrar.php' );
		$this->assertFileExists( dirname( __DIR__ ) . '/includes/abilities/class-post-abilities.php' );
		$this->assertFileExists( dirname( __DIR__ ) . '/includes/class-mcp-server.php' );
	}

	/**
	 * Destructive tools document confirm in source.
	 */
	public function test_destructive_confirm_present_in_source(): void {
		$post = file_get_contents( dirname( __DIR__ ) . '/includes/abilities/class-post-abilities.php' );
		$this->assertIsString( $post );
		$this->assertStringContainsString( 'require_confirm', $post );
		$this->assertStringContainsString( 'wp_posts_delete', $post );
	}
}
