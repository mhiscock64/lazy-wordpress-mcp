import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { fail, ok } from './helpers.js';
import type { WpUser } from '../wp/types.js';

export function registerUserTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  if (isToolAllowed(cfg, 'wp_users_list')) {
    server.tool(
      'wp_users_list',
      'List users (limited fields)',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        search: z.string().max(200).optional(),
      },
      async (args) => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          const items = await client.get<WpUser[]>('/wp-json/wp/v2/users', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            search: args.search,
            context: 'edit',
          });
          return ok({
            items: items.map((u) => ({
              id: u.id,
              name: u.name,
              slug: u.slug,
              roles: u.roles,
            })),
          });
        } catch (e) {
          return fail(e);
        }
      },
    );
  }

  if (isToolAllowed(cfg, 'wp_users_me')) {
    server.tool('wp_users_me', 'Get current authenticated user', {}, async () => {
      try {
        if (!limiter.allow()) throw new Error('Rate limit exceeded');
        const u = await client.get<WpUser>('/wp-json/wp/v2/users/me', { context: 'edit' });
        return ok({ id: u.id, name: u.name, slug: u.slug, roles: u.roles });
      } catch (e) {
        return fail(e);
      }
    });
  }
}
