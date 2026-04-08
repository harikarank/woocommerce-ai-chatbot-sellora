# Claude Code Prompt — Token Save & PHP Resource Optimization

Audit the Sellora AI plugin for **AI token waste** and **PHP resource overhead**. Apply fixes directly. Do NOT rewrite entire files — use targeted edits only.

## Scope

Working directory contains the full plugin. Key files:
- `includes/class-aiwoo-assistant-chat-service.php` (prompt building, history, MCP flow)
- `includes/class-aiwoo-assistant-catalog-service.php` (product search & formatting)
- `includes/class-aiwoo-assistant-mcp-tools.php` (tool results)
- `includes/class-aiwoo-assistant-openai-provider.php`
- `includes/class-aiwoo-assistant-claude-provider.php`
- `includes/class-aiwoo-assistant-gemini-provider.php`
- `includes/class-aiwoo-assistant-plugin.php` (bootstrap, hooks)
- `includes/class-aiwoo-assistant-quick-reply-service.php`
- `includes/class-aiwoo-assistant-ajax-controller.php`

## Part 1 — Token Reduction

### 1A. Trim system prompt
Review `build_instructions()` and `build_instructions_mcp()`. Remove redundant sentences, merge overlapping rules, compress wording without losing meaning. Target: ≤60% of current token count.

### 1B. Shrink product context
In `Catalog_Service::format_product()`:
- Drop fields the AI rarely uses (SKU, tags, regular_price when no sale). Only include sale_price when product is on sale.
- Shorten field keys (e.g. `short_description` → `desc`).
- Strip HTML entities and excess whitespace from descriptions.

In MCP tool results (`mcp-tools.php`):
- Remove stock_status when it's "instock" (assume default).
- Omit price formatting — send raw number + currency once in system prompt.

### 1C. Smarter history
In `build_input()` / `build_messages_mcp()`:
- Reduce history from 6 to 4 entries.
- Truncate each entry from 1000 to 500 chars.
- For assistant messages in history, send only the first sentence (the conversational part), strip product card text entirely.

### 1D. Page context
- Only send `pageContext.product` when the user message references the current product (keyword overlap check). Otherwise omit it.
- Remove `pageUrl` from AI context — it adds tokens with zero AI value.

### 1E. Temperature
If temperature is above 0.5, log a one-time admin notice suggesting 0.3 for tighter responses (fewer tokens in output).

## Part 2 — PHP Resource Optimization

### 2A. Lazy loading in Plugin constructor
`Plugin::__construct()` currently instantiates ALL service objects on every request. Change to:
- Only instantiate `Settings` and `IP_Blocker` on every request.
- Defer `Chat_Service`, `Catalog_Service`, `MCP_Tools`, `Chat_Logger`, `Quick_Reply_Service` until first AJAX call (lazy init via a private getter method or `init` hook conditional on `wp_doing_ajax()`).
- `Admin_Menu` only on `is_admin()`.

### 2B. Quick Reply transient
`Quick_Reply_Service::find_match()` loads all rules from DB/transient on every chat request. If transient exists, this is fine. But verify:
- Transient TTL is 300s — increase to 3600s (rules rarely change).
- Cache invalidation already flushes on save/delete — confirm this.

### 2C. WP_Query optimization in Catalog_Service
In `find_relevant_products()`:
- Add `'update_post_meta_cache' => false` and `'update_post_term_cache' => false` if not already present.
- Use `'posts_per_page'` equal to the actual limit needed (not a larger number filtered in PHP).
- If multiple keyword queries run, batch them into a single query using OR taxonomy/meta logic where possible.

### 2D. Transient caching for MCP tools
Verify all 4 MCP tools use transient caching (120s). If `get_product_details` fetches the same product within a session, it should hit cache. Confirm keys are correct and collisions are impossible.

### 2E. Chat Logger writes
`Chat_Logger::log()` does a DB insert on every message. This is fine, but ensure it uses `$wpdb->insert()` (not a raw query) and does NOT trigger autoload of the table schema check on every call.

### 2F. Admin asset loading
Confirm `admin.js` and admin CSS only enqueue on Sellora AI admin pages (check the hook suffix filter in `Plugin`). If it loads on all admin pages, scope it.

## Constraints

- PHP 8.0+ compatibility required.
- Do not break the existing settings structure or frontend data contract.
- Run `php -l` on every changed file.
- After all changes, list estimated token savings per optimization (rough %).
