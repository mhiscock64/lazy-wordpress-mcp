<?php
/**
 * Per-user rate limiter using transients.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Rate limiter.
 */
final class Rate_Limiter {

	/**
	 * Check and increment. Returns true if allowed.
	 */
	public static function allow( ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();
		$limit   = (int) Settings::get( 'rate_limit_per_minute', 60 );
		if ( $limit <= 0 ) {
			return true;
		}
		$key   = 'wp_mcp_content_rl_' . $user_id;
		$data  = get_transient( $key );
		$now   = time();
		if ( ! is_array( $data ) || empty( $data['start'] ) || ( $now - (int) $data['start'] ) >= 60 ) {
			set_transient( $key, array( 'start' => $now, 'count' => 1 ), 120 );
			return true;
		}
		$count = (int) ( $data['count'] ?? 0 ) + 1;
		if ( $count > $limit ) {
			return false;
		}
		set_transient( $key, array( 'start' => (int) $data['start'], 'count' => $count ), 120 );
		return true;
	}

	/**
	 * Guard used inside ability execute callbacks.
	 *
	 * @return true|\WP_Error
	 */
	public static function guard() {
		if ( ! self::allow() ) {
			return new \WP_Error(
				'wp_mcp_content_rate_limited',
				__( 'Rate limit exceeded for MCP Content tools. Try again shortly.', 'wp-mcp-content' ),
				array( 'status' => 429 )
			);
		}
		return true;
	}
}
