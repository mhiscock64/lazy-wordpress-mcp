import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { requireConfirm, requireWrites } from '../security/validation.js';
import { fail, ok } from './helpers.js';

const ALLOWED = ['title', 'description', 'timezone_string', 'date_format', 'time_format'] as const;

export function registerSettingsTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  if (isToolAllowed(cfg, 'wp_settings_get')) {
    server.tool('wp_settings_get', 'Read selected site settings', {}, async () => {
      try {
        if (!limiter.allow()) throw new Error('Rate limit exceeded');
        const s = await client.get<Record<string, unknown>>('/wp-json/wp/v2/settings');
        const out: Record<string, unknown> = {};
        for (const k of ALLOWED) out[k === 'title' ? 'blogname' : k === 'description' ? 'blogdescription' : k] = s[k];
        return ok(out);
      } catch (e) {
        return fail(e);
      }
    });
  }

  if (isToolAllowed(cfg, 'wp_settings_update')) {
    server.tool(
      'wp_settings_update',
      'Update selected settings (gated). Requires confirm: true and MCP_ENABLE_WRITES',
      {
        blogname: z.string().max(200).optional(),
        blogdescription: z.string().max(500).optional(),
        timezone_string: z.string().max(100).optional(),
        date_format: z.string().max(50).optional(),
        time_format: z.string().max(50).optional(),
        confirm: z.boolean(),
      },
      async (args) => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          requireWrites(cfg.MCP_ENABLE_WRITES);
          requireConfirm(args);
          const body: Record<string, unknown> = {};
          if (args.blogname !== undefined) body.title = args.blogname;
          if (args.blogdescription !== undefined) body.description = args.blogdescription;
          if (args.timezone_string !== undefined) body.timezone_string = args.timezone_string;
          if (args.date_format !== undefined) body.date_format = args.date_format;
          if (args.time_format !== undefined) body.time_format = args.time_format;
          const s = await client.post<Record<string, unknown>>('/wp-json/wp/v2/settings', body);
          return ok({ updated: body, settings: s });
        } catch (e) {
          return fail(e);
        }
      },
    );
  }
}
