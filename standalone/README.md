# Standalone WordPress MCP Server

Node.js MCP server that talks to WordPress via the REST API using Application Passwords (Basic auth). Supports **stdio** (default) and **Streamable HTTP**.

## Quick start

```bash
npm install
cp .env.example .env
npm run build
npm run start:stdio
```

## Environment

| Variable | Required | Description |
|----------|----------|-------------|
| `WP_BASE_URL` | yes | Site origin, e.g. `https://example.com` |
| `WP_USERNAME` | yes* | WP username (*or OAuth token flow) |
| `WP_APPLICATION_PASSWORD` | yes* | App password (spaces stripped) |
| `MCP_TRANSPORT` | no | `stdio` (default) or `http` |
| `MCP_HTTP_HOST` | no | Default `127.0.0.1` |
| `MCP_HTTP_PORT` | no | Default `3847` |
| `MCP_HTTP_AUTH_TOKEN` | recommended for HTTP | Bearer token clients must send |
| `MCP_TOOL_ALLOWLIST` | no | Comma-separated tool names |
| `MCP_RATE_LIMIT_PER_MINUTE` | no | Default `60` |
| `MCP_ENABLE_WRITES` | no | Default `true` |

## Transports

### stdio

```bash
npm run start:stdio
```

### Streamable HTTP

```bash
MCP_TRANSPORT=http MCP_HTTP_AUTH_TOKEN=secret npm run start:http
# POST/GET/DELETE http://127.0.0.1:3847/mcp
```

CORS is locked to empty/same-origin style defaults; put TLS termination in front for remote clients.

## OAuth 2.1 + PKCE

`src/auth/oauth.ts` provides PKCE helpers and a token-refresh skeleton. Full WordPress.com / IdP integration needs a proxy that holds client secrets — do not embed secrets in desktop MCP configs. See [`../docs/AUTH.md`](../docs/AUTH.md).

## Security

- Only requests `WP_BASE_URL` (SSRF guard)
- Input size limits on bodies
- Destructive tools need `confirm: true`
- Writes gated by `MCP_ENABLE_WRITES`
- Allow-list via `MCP_TOOL_ALLOWLIST`

## Tests

```bash
npm test
```
