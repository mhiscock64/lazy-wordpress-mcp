import type { WpClient } from '../wp/client.js';
import { WpApiError } from '../wp/client.js';
import type { AppConfig } from '../config.js';
import { RateLimiter } from '../security/rate-limit.js';
import { requireWrites } from '../security/validation.js';

export type ToolResult = {
  content: Array<{ type: 'text'; text: string }>;
  isError?: boolean;
};

export function ok(data: unknown): ToolResult {
  return {
    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
  };
}

export function fail(err: unknown): ToolResult {
  let message = 'Unknown error';
  if (err instanceof WpApiError) {
    message = `WordPress error (${err.status}${err.code ? ` ${err.code}` : ''}): ${err.message}`;
  } else if (err instanceof Error) {
    message = err.message;
  }
  return {
    content: [{ type: 'text', text: message }],
    isError: true,
  };
}

export function withGuards(
  cfg: AppConfig,
  limiter: RateLimiter,
  opts: { write?: boolean },
  fn: (client: WpClient) => Promise<unknown>,
  client: WpClient,
): () => Promise<ToolResult> {
  return async () => {
    try {
      if (!limiter.allow()) {
        throw new Error('Rate limit exceeded');
      }
      if (opts.write) requireWrites(cfg.MCP_ENABLE_WRITES);
      const data = await fn(client);
      return ok(data);
    } catch (e) {
      return fail(e);
    }
  };
}

export function mapPost(p: {
  id: number;
  status: string;
  type: string;
  link?: string;
  title: { rendered: string; raw?: string };
  content: { rendered: string; raw?: string };
  excerpt: { rendered: string; raw?: string };
  author: number;
  categories?: number[];
  tags?: number[];
  date_gmt?: string;
  modified_gmt?: string;
  parent?: number;
}) {
  return {
    id: p.id,
    title: p.title.raw ?? strip(p.title.rendered),
    content: p.content.raw ?? p.content.rendered,
    excerpt: p.excerpt.raw ?? strip(p.excerpt.rendered),
    status: p.status,
    type: p.type,
    link: p.link,
    author: p.author,
    categories: p.categories ?? [],
    tags: p.tags ?? [],
    date: p.date_gmt,
    modified: p.modified_gmt,
    parent: p.parent,
  };
}

function strip(html: string): string {
  return html.replace(/<[^>]+>/g, '').trim();
}
