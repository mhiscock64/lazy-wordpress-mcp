# Build notes

Built: 2026-10-15 (America/Toronto)

## What was built

Complete dual-path WordPress MCP project at `/workspace/wordpress-mcp`:

1. **Plugin** `plugin/wp-mcp-content` — WordPress 6.9+ Abilities API abilities + custom MCP Adapter server `wp-mcp-content` (`HttpTransport`, `ErrorLogMcpErrorHandler`, `NullMcpObservabilityHandler`). Settings page, rate limiter, allow-list, write/settings-write gates, confirm on destructive ops, resources + prompts.
2. **Standalone** `standalone/` — TypeScript MCP server (`@modelcontextprotocol/sdk`) with stdio + Streamable HTTP (Express), Application Password auth, OAuth PKCE scaffolding, full tool surface mirroring the plugin via `/wp-json/wp/v2/...` and `/wp-json/wc/v3/...`.
3. **Docs** — README, SECURITY, ARCHITECTURE, TOOLS, CLIENTS, AUTH, DEPLOYMENT, FUTURE.
4. **Examples** — Claude Desktop, Cursor, VS Code, ChatGPT, generic stdio/HTTP configs + prompt examples.

## Verification run

- `npx tsc --noEmit` in `standalone/` — **passed**
- `npm run build` — **passed**
- `npm test` (vitest) — **5 tests passed**
- PHP CLI not available on the box; PHP files not linted with `php -l`

## Known gaps / limitations

- Plugin soft-depends on MCP Adapter; without it, abilities still register (WP 6.9+) but custom MCP server will not.
- Standalone `wp_site_environment` and `wp_plugins_themes_list` are degraded vs plugin (core REST does not always expose plugins/themes/env).
- Standalone SEO update writes both Yoast and Rank Math meta keys; success depends on those keys being `show_in_rest`.
- WooCommerce standalone tools need Woo REST auth (Application Passwords / keys with shop permissions).
- HTTP CORS intentionally rejects browser cross-origin; remote clients should use non-browser MCP or a reverse proxy.
- Plugin PHPUnit tests are skeletons (file/string assertions) pending a full WP test bootstrap.
- Multisite `blog_id` switching is best-effort; document per-site REST URLs for standalone.
- Not pushed to GitHub (per request). No Cursor cloud agents used.

## Main entry points

| Component | Path |
|-----------|------|
| Root README | `/workspace/wordpress-mcp/README.md` |
| Plugin bootstrap | `/workspace/wordpress-mcp/plugin/wp-mcp-content/wp-mcp-content.php` |
| Standalone entry | `/workspace/wordpress-mcp/standalone/src/index.ts` |
| Standalone built bin | `/workspace/wordpress-mcp/standalone/dist/index.js` |
