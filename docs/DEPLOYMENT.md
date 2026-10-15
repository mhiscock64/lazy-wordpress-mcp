# Production deployment

## Plugin path

1. Install WordPress **6.9+**, PHP **8.1+**.
2. Install and activate [MCP Adapter](https://github.com/WordPress/mcp-adapter) (`wordpress/mcp-adapter`) **or** `composer require wordpress/mcp-adapter` in the plugin and load vendor autoload.
3. Copy `plugin/wp-mcp-content` to `wp-content/plugins/wp-mcp-content` and activate.
4. Settings → **MCP Content**:
   - Enable only needed tools (allow-list)
   - Set rate limit (e.g. 30–60 req/min)
   - Keep **settings write** off unless required
   - Disable write tools if the HTTP endpoint is reachable beyond trusted clients
5. Create a dedicated user + Application Password.
6. Put HTTPS + WAF / rate limiting on the REST API (especially `/wp-json/wp-mcp-content/mcp` and `/wp-json/mcp/*`).
7. Optionally disable the default MCP server if you only want this custom server:

```php
add_filter( 'mcp_adapter_create_default_server', '__return_false' );
```

## Standalone path

1. Node 20+.
2. `cd standalone && npm ci && npm run build`
3. Deploy as a systemd service or container with env from a secrets manager.
4. Prefer **stdio** for local desktop clients (no open port).
5. For remote clients, run HTTP on localhost and reverse-proxy with TLS:

```nginx
location /mcp {
  proxy_pass http://127.0.0.1:3847/mcp;
  proxy_http_version 1.1;
  proxy_set_header Authorization $http_authorization;
  proxy_buffering off;
}
```

6. Set `MCP_ENABLE_WRITES=false` for read-only public agents.
7. Set `MCP_TOOL_ALLOWLIST` to the minimum tool set.
8. Set `MCP_HTTP_AUTH_TOKEN` to a long random secret.

## Lockdown checklist

- [ ] Dedicated least-privilege WP user
- [ ] App password rotated on schedule
- [ ] HTTPS / TLS verified
- [ ] Write tools off for untrusted HTTP
- [ ] Settings write off
- [ ] Rate limit + WAF
- [ ] Allow-list applied
- [ ] No secrets in git / chat logs
- [ ] Audit / activity log plugin recommended
- [ ] Multisite: document which blogs are in scope

## WP-CLI STDIO (plugin)

```bash
wp mcp-adapter serve --server=wp-mcp-content --user=mcp-editor
```

Run only on trusted hosts; the process inherits that user's capabilities.
