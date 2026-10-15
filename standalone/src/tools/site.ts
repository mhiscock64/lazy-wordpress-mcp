import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { fail, ok } from './helpers.js';

export function registerSiteTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  if (isToolAllowed(cfg, 'wp_site_info')) {
    server.tool(
      'wp_site_info',
      'Get site name, URL, description, and locale',
      {},
      async () => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          const settings = await client.get<Record<string, unknown>>('/wp-json/wp/v2/settings').catch(async () => {
            // settings may require manage_options; fall back to root
            return null;
          });
          const root = await client.get<Record<string, unknown>>('/wp-json/');
          return ok({
            name: (settings?.title as string) ?? (root.name as string) ?? null,
            description: (settings?.description as string) ?? (root.description as string) ?? null,
            url: (root.url as string) ?? cfg.wpOrigin,
            home: (root.home as string) ?? cfg.wpOrigin,
            locale: (settings?.language as string) ?? null,
            namespaces: root.namespaces ?? undefined,
          });
        } catch (e) {
          return fail(e);
        }
      },
    );
  }

  if (isToolAllowed(cfg, 'wp_site_environment')) {
    server.tool(
      'wp_site_environment',
      'Get environment hints from the REST index (limited without plugin)',
      {},
      async () => {
        try {
          if (!limiter.allow()) throw new Error('Rate limit exceeded');
          const root = await client.get<Record<string, unknown>>('/wp-json/');
          return ok({
            site: cfg.wpOrigin,
            namespaces: root.namespaces,
            authentication: root.authentication,
            note: 'Full PHP/WP version details require the wp-mcp-content plugin ability or manage_options site health.',
          });
        } catch (e) {
          return fail(e);
        }
      },
    );
  }
}

// silence unused z if needed
