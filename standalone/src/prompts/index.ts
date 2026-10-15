import { z } from 'zod';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';

export function registerPrompts(server: McpServer): void {
  server.prompt(
    'draft-post',
    'Guide drafting a WordPress post as draft via tools',
    {
      topic: z.string(),
      audience: z.string().optional(),
    },
    async ({ topic, audience }) => ({
      messages: [
        {
          role: 'user',
          content: {
            type: 'text',
            text:
              `Draft a WordPress post about "${topic}" for ${audience ?? 'general readers'}. ` +
              'Use wp_posts_create with status draft. Do not publish unless asked; then wp_posts_set_status with confirm:true.',
          },
        },
      ],
    }),
  );

  server.prompt(
    'moderate-comments',
    'Guide moderating pending comments with confirm on destructive actions',
    { focus: z.string().optional() },
    async ({ focus }) => ({
      messages: [
        {
          role: 'user',
          content: {
            type: 'text',
            text:
              `Moderate ${focus ?? 'pending comments'}. List with wp_comments_list. ` +
              'Approve with confirm:true; trash/spam with wp_comments_trash and confirm:true.',
          },
        },
      ],
    }),
  );

  server.prompt(
    'seo-audit',
    'Audit SEO title/description for a post',
    { post_id: z.string() },
    async ({ post_id }) => ({
      messages: [
        {
          role: 'user',
          content: {
            type: 'text',
            text:
              `Audit SEO for post ID ${post_id}. Use wp_posts_get and wp_seo_get. ` +
              'Propose title ≤60 and description ≤160 chars; update with wp_seo_update only if user agrees.',
          },
        },
      ],
    }),
  );
}
