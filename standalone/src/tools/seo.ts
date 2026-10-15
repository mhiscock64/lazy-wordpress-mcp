import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireWrites } from '../security/validation.js';
import { fail, ok } from './helpers.js';

type MetaBag = Record<string, unknown>;

export function registerSeoTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  if (isToolAllowed(cfg, 'wp_seo_get')) {
    server.tool(
      'wp_seo_get',
      'Get Yoast/Rank Math SEO meta for a post via post meta when exposed',
      { post_id: z.number().int().positive() },
      async ({ post_id }) => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          const post = await client.get<MetaBag>(`/wp-json/wp/v2/posts/${post_id}`, {
            context: 'edit',
          });
          const meta = (post.meta as Record<string, unknown> | undefined) ?? {};
          const yoastTitle = meta._yoast_wpseo_title;
          const yoastDesc = meta._yoast_wpseo_metadesc;
          const rmTitle = meta.rank_math_title;
          const rmDesc = meta.rank_math_description;
          if (yoastTitle !== undefined || yoastDesc !== undefined) {
            return ok({
              provider: 'yoast',
              post_id,
              title: yoastTitle ?? '',
              description: yoastDesc ?? '',
            });
          }
          if (rmTitle !== undefined || rmDesc !== undefined) {
            return ok({
              provider: 'rank_math',
              post_id,
              title: rmTitle ?? '',
              description: rmDesc ?? '',
            });
          }
          // Try Yoast REST namespace
          try {
            const y = await client.get<MetaBag>(`/wp-json/yoast/v1/get_head`, {
              url: String(post.link ?? ''),
            });
            return ok({ provider: 'yoast_head', post_id, data: y });
          } catch {
            return ok({
              provider: 'none',
              post_id,
              message:
                'SEO meta not readable via REST. Install Yoast/Rank Math with meta in REST, or use the wp-mcp-content plugin.',
            });
          }
        } catch (e) {
          return fail(e);
        }
      },
    );
  }

  if (isToolAllowed(cfg, 'wp_seo_update')) {
    server.tool(
      'wp_seo_update',
      'Update Yoast/Rank Math meta fields on a post when meta registration allows REST writes',
      {
        post_id: z.number().int().positive(),
        title: z.string().max(200).optional(),
        description: z.string().max(500).optional(),
      },
      async (args) => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          requireWrites(cfg.MCP_ENABLE_WRITES);
          const meta: Record<string, string> = {};
          if (args.title !== undefined) {
            meta._yoast_wpseo_title = args.title;
            meta.rank_math_title = args.title;
          }
          if (args.description !== undefined) {
            meta._yoast_wpseo_metadesc = args.description;
            meta.rank_math_description = args.description;
          }
          const post = await client.post(`/wp-json/wp/v2/posts/${args.post_id}`, { meta });
          return ok({ post_id: args.post_id, updated: meta, post });
        } catch (e) {
          return fail(e);
        }
      },
    );
  }
}
