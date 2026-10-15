import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireConfirm, requireWrites } from '../security/validation.js';
import { fail, mapPost, ok } from './helpers.js';
import type { WpPost } from '../wp/types.js';

export function registerPageTools(
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

  if (isToolAllowed(cfg, 'wp_pages_list')) {
    server.tool(
      'wp_pages_list',
      'List pages with pagination and filters',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        search: z.string().max(200).optional(),
        status: z.string().optional(),
      },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpPost[]>('/wp-json/wp/v2/pages', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            search: args.search,
            status: args.status ?? 'any',
            context: 'edit',
          });
          return { items: items.map(mapPost) };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_pages_get')) {
    server.tool(
      'wp_pages_get',
      'Get a single page by ID',
      { id: z.number().int().positive() },
      async ({ id }) =>
        guard(false, async () => {
          const p = await client.get<WpPost>(`/wp-json/wp/v2/pages/${id}`, { context: 'edit' });
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_pages_create')) {
    server.tool(
      'wp_pages_create',
      'Create a page (default draft). Publishing requires confirm: true',
      {
        title: z.string().min(1).max(500),
        content: z.string().max(500_000).optional(),
        excerpt: z.string().max(10_000).optional(),
        status: z.enum(['draft', 'pending', 'private', 'publish']).optional(),
        parent: z.number().int().optional(),
        confirm: z.boolean().optional(),
      },
      async (args) =>
        guard(true, async () => {
          const status = args.status ?? 'draft';
          if (status === 'publish') requireConfirm({ confirm: args.confirm });
          const p = await client.post<WpPost>('/wp-json/wp/v2/pages', {
            title: args.title,
            content: args.content ?? '',
            excerpt: args.excerpt ?? '',
            status,
            parent: args.parent,
          });
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_pages_update')) {
    server.tool(
      'wp_pages_update',
      'Update an existing page',
      {
        id: z.number().int().positive(),
        title: z.string().max(500).optional(),
        content: z.string().max(500_000).optional(),
        excerpt: z.string().max(10_000).optional(),
        parent: z.number().int().optional(),
      },
      async (args) =>
        guard(true, async () => {
          const body: Record<string, unknown> = {};
          if (args.title !== undefined) body.title = args.title;
          if (args.content !== undefined) body.content = args.content;
          if (args.excerpt !== undefined) body.excerpt = args.excerpt;
          if (args.parent !== undefined) body.parent = args.parent;
          const p = await client.post<WpPost>(`/wp-json/wp/v2/pages/${args.id}`, body);
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_pages_delete')) {
    server.tool(
      'wp_pages_delete',
      'Trash or permanently delete a page. Requires confirm: true',
      {
        id: z.number().int().positive(),
        force: z.boolean().optional(),
        confirm: z.boolean(),
      },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          await client.delete(`/wp-json/wp/v2/pages/${args.id}`, { force: args.force ?? false });
          return { id: args.id, deleted: true };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_pages_set_status')) {
    server.tool(
      'wp_pages_set_status',
      'Change page status. Requires confirm: true',
      {
        id: z.number().int().positive(),
        status: z.enum(['draft', 'pending', 'private', 'publish', 'trash']),
        confirm: z.boolean(),
      },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          const p = await client.post<WpPost>(`/wp-json/wp/v2/pages/${args.id}`, {
            status: args.status,
          });
          return mapPost(p);
        }),
    );
  }
}
