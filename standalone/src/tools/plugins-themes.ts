import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { isToolAllowed } from '../security/allowlist.js';
import { fail, ok } from './helpers.js';

export function registerPluginThemeTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  if (!isToolAllowed(cfg, 'wp_plugins_themes_list')) return;

  server.tool(
    'wp_plugins_themes_list',
    'List active plugins and current theme when REST endpoints exist (may require extra plugins)',
    {},
    async () => {
      try {
        if (!limiter.allow()) throw new Error('Rate limit exceeded');
        // Core WP does not expose plugins via REST by default; try common routes and degrade gracefully.
        let plugins: unknown = null;
        let themes: unknown = null;
        try {
          plugins = await client.get('/wp-json/wp/v2/plugins');
        } catch {
          plugins = {
            note: 'Plugins REST route unavailable. Use the wp-mcp-content plugin ability for reliable listing.',
          };
        }
        try {
          themes = await client.get('/wp-json/wp/v2/themes');
        } catch {
          themes = {
            note: 'Themes REST route unavailable without additional capabilities/plugins.',
          };
        }
        return ok({ plugins, themes });
      } catch (e) {
        return fail(e);
      }
    },
  );
}
