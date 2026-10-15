import { describe, it, expect, vi } from 'vitest';
import { mapPost, ok, fail } from '../src/tools/helpers.js';
import { WpApiError } from '../src/wp/client.js';
import { requireConfirm } from '../src/security/validation.js';
import { applicationPasswordHeader } from '../src/auth/application-password.js';

describe('posts helpers', () => {
  it('maps WP post shapes', () => {
    const mapped = mapPost({
      id: 1,
      status: 'draft',
      type: 'post',
      link: 'https://example.com/?p=1',
      title: { rendered: 'Hello', raw: 'Hello' },
      content: { rendered: '<p>Hi</p>', raw: 'Hi' },
      excerpt: { rendered: '', raw: '' },
      author: 2,
      categories: [1],
      tags: [],
    });
    expect(mapped.id).toBe(1);
    expect(mapped.title).toBe('Hello');
    expect(mapped.content).toBe('Hi');
  });

  it('ok/fail helpers', () => {
    expect(ok({ a: 1 }).isError).toBeUndefined();
    const err = fail(new WpApiError('Nope', 403, 'rest_forbidden'));
    expect(err.isError).toBe(true);
    expect(err.content[0].text).toContain('403');
  });

  it('requireConfirm', () => {
    expect(() => requireConfirm({ confirm: true })).not.toThrow();
    expect(() => requireConfirm({})).toThrow(/confirm/);
  });

  it('strips spaces from application passwords', () => {
    const h = applicationPasswordHeader('user', 'abcd efgh ijkl');
    expect(h.startsWith('Basic ')).toBe(true);
    const decoded = Buffer.from(h.slice(6), 'base64').toString('utf8');
    expect(decoded).toBe('user:abcdefghijkl');
  });
});

// Skeleton: mock list posts client
describe('wp_posts_list mock', () => {
  it('formats list payload', async () => {
    const fetchMock = vi.fn(async () => ({
      ok: true,
      text: async () =>
        JSON.stringify([
          {
            id: 9,
            status: 'publish',
            type: 'post',
            title: { rendered: 'T', raw: 'T' },
            content: { rendered: 'C', raw: 'C' },
            excerpt: { rendered: '', raw: '' },
            author: 1,
          },
        ]),
    }));
    vi.stubGlobal('fetch', fetchMock);
    const { WpClient } = await import('../src/wp/client.js');
    const client = new WpClient({
      WP_BASE_URL: 'https://example.com',
      WP_USERNAME: 'u',
      WP_APPLICATION_PASSWORD: 'p',
      MCP_TRANSPORT: 'stdio',
      MCP_HTTP_HOST: '127.0.0.1',
      MCP_HTTP_PORT: 3847,
      MCP_HTTP_AUTH_TOKEN: '',
      MCP_TOOL_ALLOWLIST: '',
      MCP_RATE_LIMIT_PER_MINUTE: 60,
      MCP_ENABLE_WRITES: true,
      toolAllowlist: null,
      wpOrigin: 'https://example.com',
    } as never);
    const posts = await client.get('/wp-json/wp/v2/posts');
    expect(Array.isArray(posts)).toBe(true);
    expect(fetchMock).toHaveBeenCalled();
    vi.unstubAllGlobals();
  });
});
