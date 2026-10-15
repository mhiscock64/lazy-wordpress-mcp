<?php
/**
 * WooCommerce abilities (optional).
 *
 * @package WP_MCP_Content
 */

declare(strict_types=1);

namespace WP_MCP_Content;

/**
 * WooCommerce abilities.
 */
final class WooCommerce_Abilities {

	/**
	 * Whether Woo is active.
	 */
	private static function woo_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'wc_get_product' );
	}

	/**
	 * Register.
	 */
	public static function register(): void {
		Ability_Registrar::register_tool(
			'wp_woo_products_list',
			array(
				'label'               => __( 'List WooCommerce products', 'wp-mcp-content' ),
				'description'         => __( 'List products when WooCommerce is active.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'search'   => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' );
				},
				'execute_callback'    => array( self::class, 'list_products' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_woo_products_get',
			array(
				'label'               => __( 'Get WooCommerce product', 'wp-mcp-content' ),
				'description'         => __( 'Get a product by ID.', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'id' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' );
				},
				'execute_callback'    => array( self::class, 'get_product' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_woo_orders_list',
			array(
				'label'               => __( 'List WooCommerce orders', 'wp-mcp-content' ),
				'description'         => __( 'List orders (read-only).', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
						'status'   => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
				},
				'execute_callback'    => array( self::class, 'list_orders' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
		Ability_Registrar::register_tool(
			'wp_woo_orders_get',
			array(
				'label'               => __( 'Get WooCommerce order', 'wp-mcp-content' ),
				'description'         => __( 'Get an order by ID (read-only).', 'wp-mcp-content' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array( 'id' => array( 'type' => 'integer' ) ),
					'required'   => array( 'id' ),
				),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
				},
				'execute_callback'    => array( self::class, 'get_order' ),
				'meta'                => Ability_Registrar::tool_meta( array( 'readonly' => true ) ),
			)
		);
	}

	/**
	 * Unavailable response.
	 *
	 * @return array<string,mixed>
	 */
	private static function unavailable(): array {
		return array(
			'woocommerce' => false,
			'message'     => __( 'WooCommerce is not active.', 'wp-mcp-content' ),
		);
	}

	/**
	 * List products.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_products( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		if ( ! self::woo_active() ) {
			Ability_Registrar::end( $ctx['switched'] );
			return self::unavailable();
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$args     = array(
			'status' => 'publish',
			'limit'  => $per_page,
			'page'   => $page,
			'return' => 'objects',
		);
		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( (string) $input['search'] );
		}
		$products = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : array();
		$items    = array();
		foreach ( $products as $product ) {
			$items[] = array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'sku'   => $product->get_sku(),
				'price' => $product->get_price(),
				'type'  => $product->get_type(),
				'status'=> $product->get_status(),
			);
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'items' => $items, 'page' => $page );
	}

	/**
	 * Get product.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_product( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		if ( ! self::woo_active() || ! function_exists( 'wc_get_product' ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return self::unavailable();
		}
		$product = wc_get_product( (int) $input['id'] );
		if ( ! $product ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Product not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$result = array(
			'id'          => $product->get_id(),
			'name'        => $product->get_name(),
			'sku'         => $product->get_sku(),
			'price'       => $product->get_price(),
			'regular_price' => $product->get_regular_price(),
			'type'        => $product->get_type(),
			'status'      => $product->get_status(),
			'description' => $product->get_description(),
			'stock_status'=> $product->get_stock_status(),
		);
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}

	/**
	 * List orders.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function list_orders( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		if ( ! self::woo_active() || ! function_exists( 'wc_get_orders' ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return self::unavailable();
		}
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 10 ) ) );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'objects',
		);
		if ( ! empty( $input['status'] ) ) {
			$args['status'] = sanitize_key( (string) $input['status'] );
		}
		$orders = wc_get_orders( $args );
		$items  = array();
		foreach ( $orders as $order ) {
			$items[] = array(
				'id'       => $order->get_id(),
				'status'   => $order->get_status(),
				'total'    => $order->get_total(),
				'currency' => $order->get_currency(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : null,
			);
		}
		Ability_Registrar::end( $ctx['switched'] );
		return array( 'items' => $items, 'page' => $page );
	}

	/**
	 * Get order.
	 *
	 * @param array<string,mixed> $input Input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_order( array $input = array() ) {
		$ctx = Ability_Registrar::begin( $input );
		if ( is_wp_error( $ctx ) ) {
			return $ctx;
		}
		if ( ! self::woo_active() || ! function_exists( 'wc_get_order' ) ) {
			Ability_Registrar::end( $ctx['switched'] );
			return self::unavailable();
		}
		$order = wc_get_order( (int) $input['id'] );
		if ( ! $order ) {
			Ability_Registrar::end( $ctx['switched'] );
			return new \WP_Error( 'not_found', __( 'Order not found.', 'wp-mcp-content' ), array( 'status' => 404 ) );
		}
		$line_items = array();
		foreach ( $order->get_items() as $item ) {
			$line_items[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'total'    => $item->get_total(),
			);
		}
		$result = array(
			'id'         => $order->get_id(),
			'status'     => $order->get_status(),
			'total'      => $order->get_total(),
			'currency'   => $order->get_currency(),
			'billing'    => array(
				'email' => $order->get_billing_email(),
				'name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			),
			'line_items' => $line_items,
			'date'       => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : null,
		);
		Ability_Registrar::end( $ctx['switched'] );
		return $result;
	}
}
