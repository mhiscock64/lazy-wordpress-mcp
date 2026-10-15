import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { registerSiteTools } from './site.js';
import { registerPostTools } from './posts.js';
import { registerPageTools } from './pages.js';
import { registerMediaTools } from './media.js';
import { registerTaxonomyTools } from './taxonomies.js';
import { registerCommentTools } from './comments.js';
import { registerUserTools } from './users.js';
import { registerSettingsTools } from './settings.js';
import { registerPluginThemeTools } from './plugins-themes.js';
import { registerSeoTools } from './seo.js';
import { registerWooTools } from './woocommerce.js';

export function registerAllTools(
  server: McpServer,
  client: WpClient,
  cfg: AppConfig,
  limiter: RateLimiter,
): void {
  registerSiteTools(server, client, cfg, limiter);
  registerPostTools(server, client, cfg, limiter);
  registerPageTools(server, client, cfg, limiter);
  registerMediaTools(server, client, cfg, limiter);
  registerTaxonomyTools(server, client, cfg, limiter);
  registerCommentTools(server, client, cfg, limiter);
  registerUserTools(server, client, cfg, limiter);
  registerSettingsTools(server, client, cfg, limiter);
  registerPluginThemeTools(server, client, cfg, limiter);
  registerSeoTools(server, client, cfg, limiter);
  registerWooTools(server, client, cfg, limiter);
}
