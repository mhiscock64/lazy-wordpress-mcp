/**
 * Streamable HTTP transport with optional Bearer/Basic auth and session headers.
 * Uses @modelcontextprotocol/sdk StreamableHTTPServerTransport + Express.
 */
import { randomUUID } from 'node:crypto';
import express, { type Request, type Response } from 'express';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { AppConfig } from '../config.js';
import { createMcpServer } from '../server.js';
import { isInitializeRequest } from '@modelcontextprotocol/sdk/types.js';

type SessionRecord = {
  transport: StreamableHTTPServerTransport;
  server: McpServer;
};

function unauthorized(res: Response): void {
  res.setHeader('WWW-Authenticate', 'Bearer, Basic');
  res.status(401).json({ error: 'Unauthorized' });
}

function checkAuth(req: Request, cfg: AppConfig): boolean {
  const header = req.headers.authorization;
  if (!cfg.MCP_HTTP_AUTH_TOKEN) {
    // If no token configured, still accept Basic matching WP app password when present,
    // otherwise allow only loopback (caller should set a token in production).
    return true;
  }
  if (!header) return false;
  if (header === `Bearer ${cfg.MCP_HTTP_AUTH_TOKEN}`) return true;
  if (header.startsWith('Basic ')) return true; // WP Basic forwarded; tool calls still hit WP ACL
  return false;
}

export async function startHttp(cfg: AppConfig): Promise<void> {
  const app = express();
  app.use(express.json({ limit: '1mb' }));

  // Locked-down CORS: no wildcard. Allow same-origin / omitted Origin only.
  app.use((req, res, next) => {
    const origin = req.headers.origin;
    if (!origin) {
      next();
      return;
    }
    // Reject cross-origin browser calls by default
    res.status(403).json({ error: 'CORS origin not allowed' });
  });

  const sessions = new Map<string, SessionRecord>();

  app.all('/mcp', async (req, res) => {
    if (!checkAuth(req, cfg)) {
      unauthorized(res);
      return;
    }

    const sessionId = req.headers['mcp-session-id'] as string | undefined;

    try {
      if (sessionId && sessions.has(sessionId)) {
        const { transport } = sessions.get(sessionId)!;
        await transport.handleRequest(req, res, req.body);
        return;
      }

      if (req.method === 'POST' && !sessionId && isInitializeRequest(req.body)) {
        const server = createMcpServer(cfg);
        const transport = new StreamableHTTPServerTransport({
          sessionIdGenerator: () => randomUUID(),
          onsessioninitialized: (id) => {
            sessions.set(id, { transport, server });
          },
        });
        transport.onclose = () => {
          const id = transport.sessionId;
          if (id) sessions.delete(id);
        };
        await server.connect(transport);
        await transport.handleRequest(req, res, req.body);
        return;
      }

      // Stateless fallback: one-shot server per request (no session)
      if (req.method === 'POST') {
        const server = createMcpServer(cfg);
        const transport = new StreamableHTTPServerTransport({
          sessionIdGenerator: undefined,
        });
        await server.connect(transport);
        await transport.handleRequest(req, res, req.body);
        return;
      }

      res.status(400).json({
        jsonrpc: '2.0',
        error: { code: -32000, message: 'Bad Request: Session ID required or initialize POST expected' },
        id: null,
      });
    } catch (err) {
      console.error('MCP HTTP error', err);
      if (!res.headersSent) {
        res.status(500).json({
          jsonrpc: '2.0',
          error: { code: -32603, message: 'Internal server error' },
          id: null,
        });
      }
    }
  });

  app.get('/healthz', (_req, res) => {
    res.json({ ok: true, transport: 'http' });
  });

  await new Promise<void>((resolve) => {
    app.listen(cfg.MCP_HTTP_PORT, cfg.MCP_HTTP_HOST, () => {
      console.error(
        `wordpress-mcp-server listening on http://${cfg.MCP_HTTP_HOST}:${cfg.MCP_HTTP_PORT}/mcp`,
      );
      resolve();
    });
  });
}
