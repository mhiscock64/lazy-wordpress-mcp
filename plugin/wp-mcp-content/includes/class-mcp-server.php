<?php
/**
 * Custom MCP server registration.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * MCP server.
 */
final class Mcp_Server {

	/**
	 * Whether adapter class is loadable.
	 */
	public static function adapter_available(): bool {
		return class_exists( '\\WP\\MCP\\Core\\McpAdapter' );
	}

	/**
	 * Register custom server on mcp_adapter_init.
	 *
	 * @param mixed $adapter Adapter from action (may be unused; we use singleton).
	 */
	public static function register( $adapter = null ): void {
		if ( ! self::adapter_available() ) {
			return;
		}

		$tools     = Ability_Registrar::ability_names_for_server();
		$resources = Resource_Registrar::ability_names();
		$prompts   = Prompt_Registrar::ability_names();

		$instance = \WP\MCP\Core\McpAdapter::instance();
		$instance->create_server(
			'wp-mcp-content',
			'wp-mcp-content',
			'mcp',
			'WordPress MCP Content',
			'Content management tools, resources, and prompts for WordPress',
			'1.0.0',
			array( \WP\MCP\Transport\HttpTransport::class ),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$tools,
			$resources,
			$prompts
		);
	}
}
