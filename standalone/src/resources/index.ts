import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { WpClient } from '../wp/client.js';
import type { WpPost } from '../wp/types.js';

export function registerResources(server: McpServer, client: WpClient): void {
  server.resource(
    'recent-posts',
    'wordpress://wp-mcp-content/recent-posts',
    { mimeType: 'application/json', description: 'Ten most recent posts' },
    async () => {
      const items = await client.get<WpPost[]>('/wp-json/wp/v2/posts', {
        per_page: 10,
        status: 'publish',
      });
      const summary = items.map((p) => ({
        id: p.id,
        title: p.title?.rendered,
        link: p.link,
        date: p.date_gmt,
      }));
      return {
        contents: [
          {
            uri: 'wordpress://wp-mcp-content/recent-posts',
            mimeType: 'application/json',
            text: JSON.stringify({ items: summary }, null, 2),
          },
        ],
      };
    },
  );

  server.resource(
    'site-health-summary',
    'wordpress://wp-mcp-content/site-health-summary',
    { mimeType: 'application/json', description: 'Lightweight site summary from REST index' },
    async () => {
      const root = await client.get<Record<string, unknown>>('/wp-json/');
      const summary = {
        name: root.name,
        url: root.url,
        home: root.home,
        namespaces: root.namespaces,
      };
      return {
        contents: [
          {
            uri: 'wordpress://wp-mcp-content/site-health-summary',
            mimeType: 'application/json',
            text: JSON.stringify(summary, null, 2),
          },
        ],
      };
    },
  );
}
