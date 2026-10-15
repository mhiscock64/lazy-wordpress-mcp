# Authentication

## Self-hosted WordPress — Application Passwords

1. Ensure the site is served over **HTTPS** (or `localhost` / `127.0.0.1`).
2. Log in as the dedicated MCP user (least privilege).
3. Users → Profile → **Application Passwords**.
4. Create a password named e.g. `mcp-claude` / `mcp-cursor`.
5. Copy the generated password (spaces optional; clients strip them).

### Plugin / remote proxy

```bash
export WP_API_URL="https://example.com"
export WP_API_USERNAME="mcp-editor"
export WP_API_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
npx -y @automattic/mcp-wordpress-remote@latest
```

### Standalone

```bash
export WP_BASE_URL="https://example.com"
export WP_USERNAME="mcp-editor"
export WP_APPLICATION_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
```

The standalone client sends `Authorization: Basic base64(username:password)` with spaces stripped from the app password.

## Standalone HTTP mode auth

When `MCP_TRANSPORT=http`:

- Set `MCP_HTTP_AUTH_TOKEN` to require `Authorization: Bearer <token>` from MCP clients.
- Alternatively, clients may send Basic auth matching the WordPress Application Password (forwarded / verified by the server).
- Bind with `MCP_HTTP_HOST=127.0.0.1` and terminate TLS at a reverse proxy.

## WordPress.com — OAuth 2.1

WordPress.com does **not** use Application Passwords the same way. Use OAuth 2.1 (PKCE recommended for public clients).

The standalone package includes `src/auth/oauth.ts` with:

- PKCE `code_verifier` / `code_challenge` helpers
- Token refresh skeleton
- Comments documenting that a full production flow needs an IdP / proxy aligned with WordPress.com's OAuth apps

Do not embed client secrets in desktop configs. Prefer a small local proxy that holds secrets and issues short-lived tokens.

## HTTPS notes

| Environment | Application Passwords |
|-------------|----------------------|
| Production HTTPS | Supported |
| `localhost` / `127.0.0.1` | Supported without HTTPS |
| HTTP LAN IP | Rejected by WordPress core |

## Capability mapping (recommended roles)

| Role | Typical tools |
|------|----------------|
| Author | Own posts/pages drafts, media upload |
| Editor | Posts/pages/comments moderation |
| Administrator | Plugins/themes list, settings read; settings write only if explicitly enabled |

Never run MCP as a Super Admin on multisite unless required.
