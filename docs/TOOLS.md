# Tools reference

All tools are available in both the WordPress plugin (as abilities under category `wp-mcp-content`) and the standalone TypeScript server.

**Destructive tools** require `confirm: true` or they reject the call.

Plugin ability names use the form `wp-mcp-content/<slug>`; MCP tool names use underscores as listed below (adapter may sanitize `/` to `-`).

## Site

### `wp_site_info`

Get site name, URL, description, locale.

- **Required capability (approx):** `read`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "blog_id": {
      "type": "integer",
      "description": "Multisite blog id (optional)"
    }
  },
  "additionalProperties": false
}
```

### `wp_site_environment`

Get WordPress/PHP versions, multisite flag, memory limits (sanitized).

- **Required capability (approx):** `manage_options or read`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

## Posts

### `wp_posts_list`

List posts with pagination and filters.

- **Required capability (approx):** `edit_posts`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer",
      "minimum": 1
    },
    "per_page": {
      "type": "integer",
      "minimum": 1,
      "maximum": 100
    },
    "search": {
      "type": "string"
    },
    "status": {
      "type": "string"
    },
    "author": {
      "type": "integer"
    },
    "blog_id": {
      "type": "integer"
    }
  },
  "additionalProperties": false
}
```

### `wp_posts_get`

Get a single post by ID.

- **Required capability (approx):** `read`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "blog_id": {
      "type": "integer"
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_posts_create`

Create a post (default status draft).

- **Required capability (approx):** `edit_posts / publish_posts`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "title": {
      "type": "string"
    },
    "content": {
      "type": "string"
    },
    "excerpt": {
      "type": "string"
    },
    "status": {
      "type": "string",
      "enum": [
        "draft",
        "pending",
        "private",
        "publish"
      ],
      "default": "draft"
    },
    "categories": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "tags": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "confirm": {
      "type": "boolean",
      "description": "Required true when status is publish"
    }
  },
  "required": [
    "title"
  ],
  "additionalProperties": false
}
```

### `wp_posts_update`

Update an existing post.

- **Required capability (approx):** `edit_post`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "title": {
      "type": "string"
    },
    "content": {
      "type": "string"
    },
    "excerpt": {
      "type": "string"
    },
    "categories": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "tags": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_posts_delete`

Trash or permanently delete a post.

- **Required capability (approx):** `delete_post`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "force": {
      "type": "boolean",
      "default": false
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "confirm"
  ],
  "additionalProperties": false
}
```

### `wp_posts_set_status`

Change post status; publish requires confirm.

- **Required capability (approx):** `edit_post / publish_posts`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "status": {
      "type": "string",
      "enum": [
        "draft",
        "pending",
        "private",
        "publish",
        "trash"
      ]
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "status",
    "confirm"
  ],
  "additionalProperties": false
}
```

## Pages

### `wp_pages_list`

List pages with pagination and filters.

- **Required capability (approx):** `edit_pages`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer",
      "minimum": 1
    },
    "per_page": {
      "type": "integer",
      "minimum": 1,
      "maximum": 100
    },
    "search": {
      "type": "string"
    },
    "status": {
      "type": "string"
    },
    "author": {
      "type": "integer"
    },
    "blog_id": {
      "type": "integer"
    }
  },
  "additionalProperties": false
}
```

### `wp_pages_get`

Get a single page by ID.

- **Required capability (approx):** `read`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "blog_id": {
      "type": "integer"
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_pages_create`

Create a page (default status draft).

- **Required capability (approx):** `edit_pages / publish_pages`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "title": {
      "type": "string"
    },
    "content": {
      "type": "string"
    },
    "excerpt": {
      "type": "string"
    },
    "status": {
      "type": "string",
      "enum": [
        "draft",
        "pending",
        "private",
        "publish"
      ],
      "default": "draft"
    },
    "categories": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "tags": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "confirm": {
      "type": "boolean",
      "description": "Required true when status is publish"
    }
  },
  "required": [
    "title"
  ],
  "additionalProperties": false
}
```

### `wp_pages_update`

Update an existing page.

- **Required capability (approx):** `edit_page`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "title": {
      "type": "string"
    },
    "content": {
      "type": "string"
    },
    "excerpt": {
      "type": "string"
    },
    "categories": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "tags": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_pages_delete`

Trash or permanently delete a page.

- **Required capability (approx):** `delete_page`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "force": {
      "type": "boolean",
      "default": false
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "confirm"
  ],
  "additionalProperties": false
}
```

### `wp_pages_set_status`

Change page status; publish requires confirm.

- **Required capability (approx):** `edit_page / publish_pages`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "status": {
      "type": "string",
      "enum": [
        "draft",
        "pending",
        "private",
        "publish",
        "trash"
      ]
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "status",
    "confirm"
  ],
  "additionalProperties": false
}
```

## Media

### `wp_media_list`

List media library items.

- **Required capability (approx):** `upload_files`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer"
    },
    "per_page": {
      "type": "integer"
    },
    "search": {
      "type": "string"
    },
    "mime_type": {
      "type": "string"
    }
  },
  "additionalProperties": false
}
```

### `wp_media_get`

Get media item by ID.

- **Required capability (approx):** `upload_files`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_media_upload`

Upload media via URL sideload or base64 (size-limited).

- **Required capability (approx):** `upload_files`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "filename": {
      "type": "string"
    },
    "url": {
      "type": "string",
      "description": "Remote URL to sideload"
    },
    "content_base64": {
      "type": "string"
    },
    "alt_text": {
      "type": "string"
    },
    "title": {
      "type": "string"
    }
  },
  "required": [
    "filename"
  ],
  "additionalProperties": false
}
```

### `wp_media_update_alt`

Update attachment alt text.

- **Required capability (approx):** `edit_post`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "alt_text": {
      "type": "string"
    }
  },
  "required": [
    "id",
    "alt_text"
  ],
  "additionalProperties": false
}
```

## Taxonomies

### `wp_taxonomies_list_categories`

List categories.

- **Required capability (approx):** `edit_posts`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "search": {
      "type": "string"
    },
    "hide_empty": {
      "type": "boolean"
    }
  },
  "additionalProperties": false
}
```

### `wp_taxonomies_list_tags`

List tags.

- **Required capability (approx):** `edit_posts`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "search": {
      "type": "string"
    }
  },
  "additionalProperties": false
}
```

### `wp_taxonomies_create_category`

Create a category.

- **Required capability (approx):** `manage_categories`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string"
    },
    "parent": {
      "type": "integer"
    },
    "description": {
      "type": "string"
    }
  },
  "required": [
    "name"
  ],
  "additionalProperties": false
}
```

### `wp_taxonomies_create_tag`

Create a tag.

- **Required capability (approx):** `edit_posts`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string"
    },
    "description": {
      "type": "string"
    }
  },
  "required": [
    "name"
  ],
  "additionalProperties": false
}
```

### `wp_taxonomies_assign`

Assign categories/tags to a post.

- **Required capability (approx):** `edit_post`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "post_id": {
      "type": "integer"
    },
    "categories": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    },
    "tags": {
      "type": "array",
      "items": {
        "type": "integer"
      }
    }
  },
  "required": [
    "post_id"
  ],
  "additionalProperties": false
}
```

## Comments

### `wp_comments_list`

List comments.

- **Required capability (approx):** `moderate_comments`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer"
    },
    "per_page": {
      "type": "integer"
    },
    "status": {
      "type": "string"
    },
    "post": {
      "type": "integer"
    }
  },
  "additionalProperties": false
}
```

### `wp_comments_approve`

Approve a comment.

- **Required capability (approx):** `moderate_comments`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "confirm"
  ],
  "additionalProperties": false
}
```

### `wp_comments_reply`

Reply to a comment.

- **Required capability (approx):** `moderate_comments`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "parent": {
      "type": "integer"
    },
    "post": {
      "type": "integer"
    },
    "content": {
      "type": "string"
    }
  },
  "required": [
    "parent",
    "post",
    "content"
  ],
  "additionalProperties": false
}
```

### `wp_comments_trash`

Trash or mark spam.

- **Required capability (approx):** `moderate_comments`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    },
    "as_spam": {
      "type": "boolean",
      "default": false
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "id",
    "confirm"
  ],
  "additionalProperties": false
}
```

## Users

### `wp_users_list`

List users (limited fields).

- **Required capability (approx):** `list_users`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer"
    },
    "per_page": {
      "type": "integer"
    },
    "search": {
      "type": "string"
    }
  },
  "additionalProperties": false
}
```

### `wp_users_me`

Get current authenticated user.

- **Required capability (approx):** `read`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

## Settings

### `wp_settings_get`

Read selected settings (blogname, blogdescription, timezone, date/time formats).

- **Required capability (approx):** `manage_options`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

### `wp_settings_update`

Update selected settings (gated; disabled by default).

- **Required capability (approx):** `manage_options + setting enabled`
- **Destructive:** `yes`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "blogname": {
      "type": "string"
    },
    "blogdescription": {
      "type": "string"
    },
    "timezone_string": {
      "type": "string"
    },
    "date_format": {
      "type": "string"
    },
    "time_format": {
      "type": "string"
    },
    "confirm": {
      "type": "boolean"
    }
  },
  "required": [
    "confirm"
  ],
  "additionalProperties": false
}
```

## Plugins/Themes

### `wp_plugins_themes_list`

List active plugins and current theme (read-only).

- **Required capability (approx):** `manage_options`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {},
  "additionalProperties": false
}
```

## SEO

### `wp_seo_get`

Get Yoast/Rank Math title & description for a post (if plugin present).

- **Required capability (approx):** `edit_post`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "post_id": {
      "type": "integer"
    }
  },
  "required": [
    "post_id"
  ],
  "additionalProperties": false
}
```

### `wp_seo_update`

Update Yoast/Rank Math title & description.

- **Required capability (approx):** `edit_post`
- **Destructive:** `no`
- **Writes:** `yes`

```json
{
  "type": "object",
  "properties": {
    "post_id": {
      "type": "integer"
    },
    "title": {
      "type": "string"
    },
    "description": {
      "type": "string"
    }
  },
  "required": [
    "post_id"
  ],
  "additionalProperties": false
}
```

## WooCommerce

### `wp_woo_products_list`

List products (Woo active).

- **Required capability (approx):** `edit_products / manage_woocommerce`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer"
    },
    "per_page": {
      "type": "integer"
    },
    "search": {
      "type": "string"
    }
  },
  "additionalProperties": false
}
```

### `wp_woo_products_get`

Get a product.

- **Required capability (approx):** `edit_products`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```

### `wp_woo_orders_list`

List orders (read).

- **Required capability (approx):** `edit_shop_orders`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "page": {
      "type": "integer"
    },
    "per_page": {
      "type": "integer"
    },
    "status": {
      "type": "string"
    }
  },
  "additionalProperties": false
}
```

### `wp_woo_orders_get`

Get an order (read).

- **Required capability (approx):** `edit_shop_orders`
- **Destructive:** `no`
- **Writes:** `no`

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "integer"
    }
  },
  "required": [
    "id"
  ],
  "additionalProperties": false
}
```
