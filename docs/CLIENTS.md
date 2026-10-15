# MCP client configuration

Example JSON files live in [`examples/configs/`](../examples/configs/).

## Claude Desktop (stdio — standalone)

Use `examples/configs/claude-desktop.stdio.json`. Place under:

- macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`
- Windows: `%APPDATA%\Claude\claude_desktop_config.json`

## Claude Desktop (HTTP proxy to WordPress)

Use `examples/configs/claude-desktop.http-proxy.json` with `@automattic/mcp-wordpress-remote`.

## Cursor

Use `examples/configs/cursor.mcp.json` (project `.cursor/mcp.json` or global MCP settings).

## VS Code

Use `examples/configs/vscode.mcp.json` with the MCP extension / Copilot MCP support.

## ChatGPT (remote)

ChatGPT typically needs **Streamable HTTP** + auth. Use `examples/configs/chatgpt.remote.http.json` pointing at your TLS-terminated standalone HTTP endpoint or the plugin REST route.

## Grok / Windsurf / Gemini

Most support stdio and/or HTTP MCP. Start from `generic.stdio.json` or `generic.http.json` and adapt keys to the client's schema (command/args vs url/headers).

## Plugin HTTP endpoint

Custom server (this plugin):

```
https://your-site.example/wp-json/wp-mcp-content/mcp
```

MCP Adapter default server:

```
https://your-site.example/wp-json/mcp/mcp-adapter-default-server
```

Authenticate with Application Passwords (Basic) unless your host adds OAuth.

## Troubleshooting clients

| Symptom | Check |
|---------|--------|
| Tools missing | Allow-list, MCP Adapter active, ability registration, `meta.mcp.public` / custom server list |
| 401/403 | App password, HTTPS, user caps |
| ChatGPT cannot connect | Streamable HTTP + Bearer/Basic; CORS; public URL |
| Stdio hangs | Absolute node/php paths; env vars in config; no interactive prompts on stdout |
