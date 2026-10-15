import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireConfirm, requireWrites } from '../security/validation.js';
import { fail, mapPost, ok } from './helpers.js';
import type { WpPost } from '../wp/types.js';

export function registerPostTools(
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

  if (isToolAllowed(cfg, 'wp_posts_list')) {
    server.tool(
      'wp_posts_list',
      'List posts with pagination and filters',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        search: z.string().max(200).optional(),
        status: z.string().optional(),
        author: z.number().int().optional(),
      },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpPost[]>('/wp-json/wp/v2/posts', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            search: args.search,
            status: args.status ?? 'any',
            author: args.author,
            context: 'edit',
          });
          return { items: items.map(mapPost) };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_posts_get')) {
    server.tool(
      'wp_posts_get',
      'Get a single post by ID',
      { id: z.number().int().positive() },
      async ({ id }) =>
        guard(false, async () => {
          const p = await client.get<WpPost>(`/wp-json/wp/v2/posts/${id}`, { context: 'edit' });
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_posts_create')) {
    server.tool(
      'wp_posts_create',
      'Create a post (default status draft). Publishing requires confirm: true',
      {
        title: z.string().min(1).max(500),
        content: z.string().max(500_000).optional(),
        excerpt: z.string().max(10_000).optional(),
        status: z.enum(['draft', 'pending', 'private', 'publish']).optional(),
        categories: z.array(z.number().int()).optional(),
        tags: z.array(z.number().int()).optional(),
        confirm: z.boolean().optional(),
      },
      async (args) =>
        guard(true, async () => {
          const status = args.status ?? 'draft';
          if (status === 'publish') requireConfirm({ confirm: args.confirm });
          const p = await client.post<WpPost>('/wp-json/wp/v2/posts', {
            title: args.title,
            content: args.content ?? '',
            excerpt: args.excerpt ?? '',
            status,
            categories: args.categories,
            tags: args.tags,
          });
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_posts_update')) {
    server.tool(
      'wp_posts_update',
      'Update an existing post',
      {
        id: z.number().int().positive(),
        title: z.string().max(500).optional(),
        content: z.string().max(500_000).optional(),
        excerpt: z.string().max(10_000).optional(),
        categories: z.array(z.number().int()).optional(),
        tags: z.array(z.number().int()).optional(),
      },
      async (args) =>
        guard(true, async () => {
          const body: Record<string, unknown> = {};
          if (args.title !== undefined) body.title = args.title;
          if (args.content !== undefined) body.content = args.content;
          if (args.excerpt !== undefined) body.excerpt = args.excerpt;
          if (args.categories !== undefined) body.categories = args.categories;
          if (args.tags !== undefined) body.tags = args.tags;
          const p = await client.post<WpPost>(`/wp-json/wp/v2/posts/${args.id}`, body);
          return mapPost(p);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_posts_delete')) {
    server.tool(
      'wp_posts_delete',
      'Trash or permanently delete a post. Requires confirm: true',
      {
        id: z.number().int().positive(),
        force: z.boolean().optional(),
        confirm: z.boolean(),
      },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          const p = await client.delete<WpPost>(`/wp-json/wp/v2/posts/${args.id}`, {
            force: args.force ?? false,
          });
          return { id: args.id, deleted: true, previous: mapPost(p) };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_posts_set_status')) {
    server.tool(
      'wp_posts_set_status',
      'Change post status. Requires confirm: true',
      {
        id: z.number().int().positive(),
        status: z.enum(['draft', 'pending', 'private', 'publish', 'trash']),
        confirm: z.boolean(),
      },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          const p = await client.post<WpPost>(`/wp-json/wp/v2/posts/${args.id}`, {
            status: args.status,
          });
          return mapPost(p);
        }),
    );
  }
}
