import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireConfirm, requireWrites } from '../security/validation.js';
import { fail, ok } from './helpers.js';
import type { WpComment } from '../wp/types.js';

function mapComment(c: WpComment) {
  return {
    id: c.id,
    post: c.post,
    parent: c.parent,
    author_name: c.author_name,
    author_email: c.author_email,
    content: c.content?.raw ?? c.content?.rendered,
    status: c.status,
    date: c.date_gmt,
  };
}

export function registerCommentTools(
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

  if (isToolAllowed(cfg, 'wp_comments_list')) {
    server.tool(
      'wp_comments_list',
      'List comments',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        status: z.string().optional(),
        post: z.number().int().optional(),
      },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpComment[]>('/wp-json/wp/v2/comments', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            status: args.status ?? 'hold',
            post: args.post,
            context: 'edit',
          });
          return { items: items.map(mapComment) };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_comments_approve')) {
    server.tool(
      'wp_comments_approve',
      'Approve a comment. Requires confirm: true',
      { id: z.number().int().positive(), confirm: z.boolean() },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          const c = await client.post<WpComment>(`/wp-json/wp/v2/comments/${args.id}`, {
            status: 'approved',
          });
          return mapComment(c);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_comments_reply')) {
    server.tool(
      'wp_comments_reply',
      'Reply to a comment',
      {
        parent: z.number().int().positive(),
        post: z.number().int().positive(),
        content: z.string().min(1).max(50_000),
      },
      async (args) =>
        guard(true, async () => {
          const c = await client.post<WpComment>('/wp-json/wp/v2/comments', {
            parent: args.parent,
            post: args.post,
            content: args.content,
          });
          return mapComment(c);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_comments_trash')) {
    server.tool(
      'wp_comments_trash',
      'Trash or mark spam. Requires confirm: true',
      {
        id: z.number().int().positive(),
        as_spam: z.boolean().optional(),
        confirm: z.boolean(),
      },
      async (args) =>
        guard(true, async () => {
          requireConfirm(args);
          if (args.as_spam) {
            const c = await client.post<WpComment>(`/wp-json/wp/v2/comments/${args.id}`, {
              status: 'spam',
            });
            return { action: 'spam', comment: mapComment(c) };
          }
          await client.delete(`/wp-json/wp/v2/comments/${args.id}`);
          return { id: args.id, action: 'trash', ok: true };
        }),
    );
  }
}
