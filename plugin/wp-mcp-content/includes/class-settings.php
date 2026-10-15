<?php
/**
 * Settings page and option helpers.
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * Settings.
 */
final class Settings {

	public const OPTION = 'wp_mcp_content_settings';

	/**
	 * Init hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'register' ) );
	}

	/**
	 * Defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'enable_writes'        => true,
			'enable_settings_write'=> false,
			'rate_limit_per_minute'=> 60,
			'allowed_tools'        => array_keys( Ability_Registrar::tool_slugs() ),
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @param string|null $key Key.
	 * @param mixed       $default Default.
	 * @return mixed
	 */
	public static function get( ?string $key = null, $default = null ) {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$all = array_merge( self::defaults(), $stored );
		if ( null === $key ) {
			return $all;
		}
		return $all[ $key ] ?? $default;
	}

	/**
	 * Whether a tool slug is allow-listed.
	 */
	public static function is_tool_allowed( string $slug ): bool {
		$allowed = self::get( 'allowed_tools', array() );
		if ( ! is_array( $allowed ) || empty( $allowed ) ) {
			return true;
		}
		return in_array( $slug, $allowed, true );
	}

	/**
	 * Writes enabled.
	 */
	public static function writes_enabled(): bool {
		return (bool) self::get( 'enable_writes', true );
	}

	/**
	 * Settings write enabled.
	 */
	public static function settings_write_enabled(): bool {
		return (bool) self::get( 'enable_settings_write', false );
	}

	/**
	 * Admin menu.
	 */
	public static function menu(): void {
		add_options_page(
			__( 'MCP Content', 'wp-mcp-content' ),
			__( 'MCP Content', 'wp-mcp-content' ),
			'manage_options',
			'wp-mcp-content',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Register setting.
	 */
	public static function register(): void {
		register_setting(
			'wp_mcp_content',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize.
	 *
	 * @param mixed $input Raw.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ): array {
		$input   = is_array( $input ) ? $input : array();
		$out     = self::defaults();
		$out['enable_writes']         = ! empty( $input['enable_writes'] );
		$out['enable_settings_write'] = ! empty( $input['enable_settings_write'] );
		$out['rate_limit_per_minute'] = max( 0, (int) ( $input['rate_limit_per_minute'] ?? 60 ) );
		$valid                        = array_keys( Ability_Registrar::tool_slugs() );
		$chosen                       = isset( $input['allowed_tools'] ) && is_array( $input['allowed_tools'] )
			? array_map( 'sanitize_text_field', $input['allowed_tools'] )
			: array();
		$out['allowed_tools']         = array_values( array_intersect( $valid, $chosen ) );
		return $out;
	}

	/**
	 * Render settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get();
		$tools    = Ability_Registrar::tool_slugs();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'MCP Content', 'wp-mcp-content' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'wp_mcp_content' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable write tools', 'wp-mcp-content' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enable_writes]" value="1" <?php checked( ! empty( $settings['enable_writes'] ) ); ?> />
								<?php esc_html_e( 'Allow create/update/delete tools for users who pass capability checks (default on for admins configuring the site).', 'wp-mcp-content' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable settings write', 'wp-mcp-content' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enable_settings_write]" value="1" <?php checked( ! empty( $settings['enable_settings_write'] ) ); ?> />
								<?php esc_html_e( 'Allow updating blogname/description/timezone/date formats via MCP (default off).', 'wp-mcp-content' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rate limit (requests/min)', 'wp-mcp-content' ); ?></th>
						<td>
							<input type="number" min="0" name="<?php echo esc_attr( self::OPTION ); ?>[rate_limit_per_minute]" value="<?php echo esc_attr( (string) $settings['rate_limit_per_minute'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tool allow-list', 'wp-mcp-content' ); ?></th>
						<td>
							<?php foreach ( $tools as $slug => $label ) : ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[allowed_tools][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $settings['allowed_tools'], true ) ); ?> />
									<code><?php echo esc_html( $slug ); ?></code> — <?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p>
				<?php
				echo esc_html__(
					'Custom MCP HTTP endpoint: /wp-json/wp-mcp-content/mcp — Multisite: pass blog_id only when the user can manage that site.',
					'wp-mcp-content'
				);
				?>
			</p>
		</div>
		<?php
	}
}
