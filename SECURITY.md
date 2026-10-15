# Security Policy

## Supported versions

Security fixes are applied to the latest `main` of this repository (plugin + standalone).

## Reporting a vulnerability

Please report security issues privately. Do **not** open a public GitHub issue for vulnerabilities involving credentials, RCE, auth bypass, or privilege escalation.

Include:
- Affected component (plugin `wp-mcp-content` or standalone `wordpress-mcp-server`)
- WordPress / PHP / Node versions
- Steps to reproduce (minimal)
- Impact assessment

## Hardening checklist (operators)

1. **Dedicated MCP user** — create a WordPress user with the least capabilities needed (e.g. Author/Editor). Prefer Application Passwords scoped to that user.
2. **HTTPS everywhere** — Application Passwords require HTTPS except on localhost.
3. **Tool allow-list** — disable unused tools in Settings → MCP Content (plugin) or `MCP_TOOL_ALLOWLIST` (standalone).
4. **Disable writes on public HTTP** — set `MCP_ENABLE_WRITES=false` (standalone) or turn off write tools when exposing Streamable HTTP beyond a trusted network.
5. **Settings write off by default** — plugin setting "Enable settings write" defaults to off.
6. **Rate limiting** — configure requests/minute; put a reverse proxy / WAF in front of HTTP transports.
7. **Never commit secrets** — no Application Passwords, OAuth tokens, or `.env` files in git.
8. **Rotate credentials** — revoke Application Passwords after staff changes or suspected leak.
9. **Audit** — enable WordPress activity logging (or ship MCP error logs) for create/update/delete actions.
10. **Confirm destructive ops** — tools that delete or publish require `confirm: true`.

## Threat model notes

- MCP clients act with the WordPress user's privileges. A compromised client = that user's capabilities.
- Standalone HTTP mode should require `MCP_HTTP_AUTH_TOKEN` (Bearer) and/or Basic auth; bind to localhost behind TLS-terminated reverse proxy when possible.
- The standalone server only calls the configured `WP_BASE_URL` (SSRF protection).
- No arbitrary code execution tools are provided.
