import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { AppConfig } from './config.js';
import { WpClient } from './wp/client.js';
import { RateLimiter } from './security/rate-limit.js';
import { registerAllTools } from './tools/index.js';
import { registerResources } from './resources/index.js';
import { registerPrompts } from './prompts/index.js';

export function createMcpServer(cfg: AppConfig): McpServer {
  const server = new McpServer({
    name: 'wordpress-mcp-server',
    version: '1.0.0',
  });

  const client = new WpClient(cfg);
  const limiter = new RateLimiter(cfg.MCP_RATE_LIMIT_PER_MINUTE);

  registerAllTools(server, client, cfg, limiter);
  registerResources(server, client);
  registerPrompts(server);

  return server;
}
