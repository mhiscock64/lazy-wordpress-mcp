import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { fail, ok } from './helpers.js';

export function registerWooTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  const guard = async (fn: () => Promise<unknown>) => {
    try {
      if (!limiter.allow()) throw new Error('Rate limit exceeded');
      return ok(await fn());
    } catch (e) {
      return fail(e);
    }
  };

  if (isToolAllowed(cfg, 'wp_woo_products_list')) {
    server.tool(
      'wp_woo_products_list',
      'List WooCommerce products (requires Woo REST)',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        search: z.string().max(200).optional(),
      },
      async (args) =>
        guard(async () =>
          client.get('/wp-json/wc/v3/products', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            search: args.search,
          }),
        ),
    );
  }

  if (isToolAllowed(cfg, 'wp_woo_products_get')) {
    server.tool(
      'wp_woo_products_get',
      'Get a WooCommerce product',
      { id: z.number().int().positive() },
      async ({ id }) => guard(async () => client.get(`/wp-json/wc/v3/products/${id}`)),
    );
  }

  if (isToolAllowed(cfg, 'wp_woo_orders_list')) {
    server.tool(
      'wp_woo_orders_list',
      'List WooCommerce orders (read)',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        status: z.string().optional(),
      },
      async (args) =>
        guard(async () =>
          client.get('/wp-json/wc/v3/orders', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            status: args.status,
          }),
        ),
    );
  }

  if (isToolAllowed(cfg, 'wp_woo_orders_get')) {
    server.tool(
      'wp_woo_orders_get',
      'Get a WooCommerce order (read)',
      { id: z.number().int().positive() },
      async ({ id }) => guard(async () => client.get(`/wp-json/wc/v3/orders/${id}`)),
    );
  }
}
