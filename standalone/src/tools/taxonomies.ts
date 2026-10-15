import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireWrites } from '../security/validation.js';
import { fail, ok } from './helpers.js';
import type { WpTerm } from '../wp/types.js';

export function registerTaxonomyTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  const guard = async (write: boolean, fn: () => Promise<unknown>) => {
    try {
      if (!limiter.allow()) throw new Error('Rate limit exceeded');
      if (write) requireWrites(cfg.MCP_ENABLE_WRITES);
      return ok(await fn());
    } catch (e) {
      return fail(e);
    }
  };

  if (isToolAllowed(cfg, 'wp_taxonomies_list_categories')) {
    server.tool(
      'wp_taxonomies_list_categories',
      'List categories',
      {
        search: z.string().max(200).optional(),
        hide_empty: z.boolean().optional(),
      },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpTerm[]>('/wp-json/wp/v2/categories', {
            search: args.search,
            hide_empty: args.hide_empty ?? false,
            per_page: 100,
          });
          return { items };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_taxonomies_list_tags')) {
    server.tool(
      'wp_taxonomies_list_tags',
      'List tags',
      { search: z.string().max(200).optional() },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpTerm[]>('/wp-json/wp/v2/tags', {
            search: args.search,
            per_page: 100,
          });
          return { items };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_taxonomies_create_category')) {
    server.tool(
      'wp_taxonomies_create_category',
      'Create a category',
      {
        name: z.string().min(1).max(200),
        parent: z.number().int().optional(),
        description: z.string().max(2000).optional(),
      },
      async (args) =>
        guard(true, async () =>
          client.post<WpTerm>('/wp-json/wp/v2/categories', {
            name: args.name,
            parent: args.parent,
            description: args.description,
          }),
        ),
    );
  }

  if (isToolAllowed(cfg, 'wp_taxonomies_create_tag')) {
    server.tool(
      'wp_taxonomies_create_tag',
      'Create a tag',
      {
        name: z.string().min(1).max(200),
        description: z.string().max(2000).optional(),
      },
      async (args) =>
        guard(true, async () =>
          client.post<WpTerm>('/wp-json/wp/v2/tags', {
            name: args.name,
            description: args.description,
          }),
        ),
    );
  }

  if (isToolAllowed(cfg, 'wp_taxonomies_assign')) {
    server.tool(
      'wp_taxonomies_assign',
      'Assign categories/tags to a post',
      {
        post_id: z.number().int().positive(),
        categories: z.array(z.number().int()).optional(),
        tags: z.array(z.number().int()).optional(),
      },
      async (args) =>
        guard(true, async () => {
          const body: Record<string, unknown> = {};
          if (args.categories) body.categories = args.categories;
          if (args.tags) body.tags = args.tags;
          return client.post(`/wp-json/wp/v2/posts/${args.post_id}`, body);
        }),
    );
  }
}
