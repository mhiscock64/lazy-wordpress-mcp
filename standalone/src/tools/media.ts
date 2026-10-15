import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireWrites } from '../security/validation.js';
import { fail, ok } from './helpers.js';
import type { WpMedia } from '../wp/types.js';

function mapMedia(m: WpMedia) {
  return {
    id: m.id,
    title: m.title?.rendered,
    url: m.source_url,
    mime_type: m.mime_type,
    alt_text: m.alt_text,
    date: m.date_gmt,
  };
}

export function registerMediaTools(
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

  if (isToolAllowed(cfg, 'wp_media_list')) {
    server.tool(
      'wp_media_list',
      'List media library items',
      {
        page: z.number().int().min(1).optional(),
        per_page: z.number().int().min(1).max(100).optional(),
        search: z.string().max(200).optional(),
        mime_type: z.string().optional(),
      },
      async (args) =>
        guard(false, async () => {
          const items = await client.get<WpMedia[]>('/wp-json/wp/v2/media', {
            page: args.page ?? 1,
            per_page: args.per_page ?? 10,
            search: args.search,
            media_type: args.mime_type,
          });
          return { items: items.map(mapMedia) };
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_media_get')) {
    server.tool(
      'wp_media_get',
      'Get media item by ID',
      { id: z.number().int().positive() },
      async ({ id }) =>
        guard(false, async () => mapMedia(await client.get<WpMedia>(`/wp-json/wp/v2/media/${id}`))),
    );
  }

  if (isToolAllowed(cfg, 'wp_media_upload')) {
    server.tool(
      'wp_media_upload',
      'Upload media via remote URL (fetched server-side to WP) or base64 (max ~5MiB). Prefer URL sideload when possible.',
      {
        filename: z.string().min(1).max(200),
        url: z.string().url().optional(),
        content_base64: z.string().max(7_000_000).optional(),
        alt_text: z.string().max(500).optional(),
        title: z.string().max(500).optional(),
      },
      async (args) =>
        guard(true, async () => {
          let bytes: Buffer;
          if (args.url) {
            // Fetch only from caller-provided URL through this server — WP will store via multipart
            const res = await fetch(args.url);
            if (!res.ok) throw new Error(`Failed to download source URL: HTTP ${res.status}`);
            const ab = await res.arrayBuffer();
            if (ab.byteLength > 5_242_880) throw new Error('Downloaded file exceeds 5 MiB');
            bytes = Buffer.from(ab);
          } else if (args.content_base64) {
            bytes = Buffer.from(args.content_base64, 'base64');
            if (bytes.length > 5_242_880) throw new Error('Decoded file exceeds 5 MiB');
          } else {
            throw new Error('Provide url or content_base64');
          }
          const form = new FormData();
          const ab = bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength) as ArrayBuffer;
          form.append('file', new Blob([ab]), args.filename);
          if (args.title) form.append('title', args.title);
          if (args.alt_text) form.append('alt_text', args.alt_text);
          const m = await client.postForm<WpMedia>('/wp-json/wp/v2/media', form);
          return mapMedia(m);
        }),
    );
  }

  if (isToolAllowed(cfg, 'wp_media_update_alt')) {
    server.tool(
      'wp_media_update_alt',
      'Update attachment alt text',
      {
        id: z.number().int().positive(),
        alt_text: z.string().max(500),
      },
      async (args) =>
        guard(true, async () => {
          const m = await client.post<WpMedia>(`/wp-json/wp/v2/media/${args.id}`, {
            alt_text: args.alt_text,
          });
          return mapMedia(m);
        }),
    );
  }
}
