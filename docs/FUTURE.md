# Future work

## Commerce

- WooCommerce write tools (create/update products, refunds) behind explicit confirm + role gates
- Subscriptions / Memberships read tools
- Store API alignment for headless shops

## Page builders & SEO

- Elementor / Divi / Gutenberg pattern-aware updates (structured blocks, not raw HTML clobbering)
- Full Rank Math / Yoast schema, canonical, robots, Open Graph batches
- ACF / meta field typed tools via registered field definitions

## Safety & ops

- Undo / revision restore tools tied to WP revisions
- Append-only audit log of MCP tool calls (user, tool, args hash, result)
- Webhooks → MCP resource notifications on post publish
- Soft-delete quarantine period before permanent delete

## Protocol & hosting

- GraphQL (WPGraphQL) transport option for complex queries
- WordPress.com OAuth 2.1 connector alignment (shared token broker)
- Native ChatGPT Apps / remote MCP marketplace packaging
- Multi-site blog picker UI in settings

## DX

- Generated OpenAPI → Zod from WP REST schema
- Contract tests against a disposable WP Docker stack
- Ability → TypeScript tool codegen from PHP schemas
