import { applicationPasswordHeader } from '../auth/application-password.js';
import { bearerHeader } from '../auth/oauth.js';
import type { AppConfig } from '../config.js';
import type { WpErrorBody } from './types.js';

const MAX_RESPONSE_CHARS = 2_000_000;

export class WpApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly code?: string,
  ) {
    super(message);
    this.name = 'WpApiError';
  }
}

export class WpClient {
  private readonly origin: string;
  private readonly authHeader: string;

  constructor(cfg: AppConfig) {
    this.origin = cfg.wpOrigin;
    if (cfg.OAUTH_ACCESS_TOKEN) {
      this.authHeader = bearerHeader(cfg.OAUTH_ACCESS_TOKEN);
    } else {
      this.authHeader = applicationPasswordHeader(cfg.WP_USERNAME!, cfg.WP_APPLICATION_PASSWORD!);
    }
  }

  private assertUrl(path: string): URL {
    if (!path.startsWith('/')) {
      throw new Error('WP path must be absolute starting with /');
    }
    const url = new URL(path, this.origin);
    if (url.origin !== this.origin) {
      throw new Error('Refusing SSRF: URL escaped WP_BASE_URL origin');
    }
    return url;
  }

  async request<T>(
    method: string,
    path: string,
    options: {
      query?: Record<string, string | number | boolean | undefined>;
      body?: unknown;
      formData?: FormData;
      headers?: Record<string, string>;
    } = {},
  ): Promise<T> {
    const url = this.assertUrl(path);
    if (options.query) {
      for (const [k, v] of Object.entries(options.query)) {
        if (v !== undefined && v !== '') url.searchParams.set(k, String(v));
      }
    }

    const headers: Record<string, string> = {
      Accept: 'application/json',
      Authorization: this.authHeader,
      ...(options.headers ?? {}),
    };

    let body: BodyInit | undefined;
    if (options.formData) {
      body = options.formData;
    } else if (options.body !== undefined) {
      headers['Content-Type'] = 'application/json';
      const json = JSON.stringify(options.body);
      if (json.length > 1_000_000) {
        throw new WpApiError('Request body too large', 400);
      }
      body = json;
    }

    const res = await fetch(url, { method, headers, body });
    const text = await res.text();
    if (text.length > MAX_RESPONSE_CHARS) {
      throw new WpApiError('Response too large', 502);
    }

    let data: unknown = null;
    if (text) {
      try {
        data = JSON.parse(text);
      } catch {
        data = { raw: text.slice(0, 2000) };
      }
    }

    if (!res.ok) {
      const err = data as WpErrorBody;
      throw new WpApiError(
        err.message ?? `WordPress REST error HTTP ${res.status}`,
        res.status,
        err.code,
      );
    }
    return data as T;
  }

  get<T>(path: string, query?: Record<string, string | number | boolean | undefined>) {
    return this.request<T>('GET', path, { query });
  }

  post<T>(path: string, body?: unknown) {
    return this.request<T>('POST', path, { body });
  }

  postForm<T>(path: string, formData: FormData) {
    return this.request<T>('POST', path, { formData });
  }

  patch<T>(path: string, body?: unknown) {
    return this.request<T>('POST', path, {
      body,
      headers: { 'X-HTTP-Method-Override': 'PUT' },
    });
    // WP REST accepts POST with method override; also try native:
  }

  async update<T>(path: string, body?: unknown) {
    try {
      return await this.request<T>('POST', path, { body });
    } catch (e) {
      if (e instanceof WpApiError && e.status === 405) {
        return this.request<T>('PUT', path, { body });
      }
      throw e;
    }
  }

  delete<T>(path: string, query?: Record<string, string | number | boolean | undefined>) {
    return this.request<T>('DELETE', path, { query });
  }
}
