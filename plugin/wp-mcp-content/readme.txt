=== WordPress MCP Content Bridge ===
Contributors: wordpress-mcp
Tags: mcp, ai, abilities, rest-api, content
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Expose WordPress content operations as MCP tools via the Abilities API and MCP Adapter.

== Description ==

Registers abilities for posts, pages, media, taxonomies, comments, users, settings (read), plugins/themes (read), SEO (Yoast/Rank Math), and WooCommerce (read). Creates a custom MCP server `wp-mcp-content`.

Requires the official MCP Adapter plugin (or Composer package wordpress/mcp-adapter).

== Installation ==

1. Install and activate MCP Adapter.
2. Upload this plugin to wp-content/plugins/wp-mcp-content.
3. Activate. Configure under Settings → MCP Content.

== Changelog ==

= 1.0.0 =
* Initial release.
