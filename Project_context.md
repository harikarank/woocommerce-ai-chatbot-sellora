# Sellora AI — WooCommerce AI Chatbot & Shopping Assistant

## Branding

| Field | Value |
|---|---|
| **Plugin name** | WooCommerce AI Chatbot & Shopping Assistant - Sellora AI |
| **Brand name** | Sellora AI |
| **WordPress slug** | `woocommerce-ai-chatbot-sellora` |
| **Plugin URI** | https://selloraii.com |
| **Author** | Sellora AI |

> **Note:** The physical folder and main file are still named `ai-woocommerce-assistant` (the original scaffold name). To match the slug `woocommerce-ai-chatbot-sellora` on WordPress.org, rename the folder to `woocommerce-ai-chatbot-sellora` and the main PHP file to `woocommerce-ai-chatbot-sellora.php`, then update the three `require_once` paths inside the new main file and reactivate the plugin in WordPress.

## Overview

This repository contains a WordPress plugin named **Sellora AI**.

**Short description:** AI-powered WooCommerce chatbot and shopping assistant that helps customers find products, get instant answers, and boost sales with smart recommendations.

**Full description:** Sellora AI is a powerful WooCommerce AI chatbot and shopping assistant designed to increase conversions, improve customer experience, and automate product discovery in your store. With seamless AI integration, Sellora AI allows your customers to chat naturally, discover products faster, and receive intelligent recommendations — all in real time.

Its purpose is to:
- render a floating storefront chat widget branded as Sellora AI
- search WooCommerce products based on user input
- send matched product context to an AI provider (legacy mode) or let the AI fetch data via MCP tool calls (MCP mode)
- return conversational product recommendations with product cards
- support personalised recommendations based on browsing and search history
- provide upsell / cross-sell intelligence using WooCommerce linked products
- fall back to an enquiry form when no product match is found

The current implementation is structured as a production-style plugin scaffold with modular backend services, AJAX handlers, frontend assets, and WordPress settings integration.

## Current File Structure

```text
/woocommerce-ai-chatbot-sellora.php
/index.php
/readme.txt
/uninstall.php
/Project_context.md
/admin/
  settings-page.php
  chat-history-page.php
  chat-session-detail-page.php
  enquiries-page.php
  ip-blocklist-page.php
  quick-replies-page.php
  top-requests-page.php
  ai-errors-page.php          ← session 17
  info-page.php               ← session 18
/assets/
  /css/
    style.css
  /js/
    admin.js
    chat.js
  /img/
    logo.svg                  ← session 18 (default panel logo)
    favicon.svg               ← session 18 (default launcher icon)
/includes/
  api-handler.php
  woocommerce-handler.php
  class-aiwoo-assistant-admin-menu.php
  class-aiwoo-assistant-ajax-controller.php
  class-aiwoo-assistant-ai-error-logger.php  ← session 17
  class-aiwoo-assistant-catalog-service.php
  class-aiwoo-assistant-chat-logger.php
  class-aiwoo-assistant-chat-service.php
  class-aiwoo-assistant-claude-provider.php
  class-aiwoo-assistant-gemini-provider.php
  class-aiwoo-assistant-ip-blocker.php
  class-aiwoo-assistant-mcp-tools.php
  class-aiwoo-assistant-openai-provider.php
  class-aiwoo-assistant-plugin.php
  class-aiwoo-assistant-provider-interface.php
  class-aiwoo-assistant-quick-reply-service.php
  class-aiwoo-assistant-settings.php
/templates/
  chat-widget.php
```

## Main Runtime Flow

### 1. Plugin bootstrap

File: `woocommerce-ai-chatbot-sellora.php`

Responsibilities:
- declares plugin headers and constants (`AI_WOO_ASSISTANT_VERSION`, `AI_WOO_ASSISTANT_FILE`, `AI_WOO_ASSISTANT_PATH`, `AI_WOO_ASSISTANT_URL`)
- loads:
  - `includes/class-aiwoo-assistant-settings.php`
  - `includes/class-aiwoo-assistant-chat-logger.php`
  - `includes/class-aiwoo-assistant-admin-menu.php`
  - `includes/woocommerce-handler.php`
  - `includes/api-handler.php`
  - `includes/class-aiwoo-assistant-plugin.php`
- registers activation hook → `Chat_Logger::create_table()`
- starts the plugin singleton via `\AIWooAssistant\Plugin::instance()`

### 2. Core plugin initialization

File: `includes/class-aiwoo-assistant-plugin.php`

Responsibilities:
- calls `Chat_Logger::maybe_create_table()` (schema upgrade guard)
- creates the main service objects:
  - `Settings`
  - `Catalog_Service`
  - `Chat_Service`
  - `Chat_Logger`
  - `IP_Blocker`
  - `Ajax_Controller`
  - `Admin_Menu`
- registers WordPress hooks for:
  - textdomain loading
  - WooCommerce compatibility declaration
  - hidden enquiry post type registration
  - missing-WooCommerce admin notice (hooked on `admin_init`, not `init`)
  - admin asset loading (scoped to settings hook suffix from `Admin_Menu::get_settings_hook()`)
  - frontend asset loading
  - frontend widget rendering in `wp_footer`

### 3. Frontend widget

Files:
- `templates/chat-widget.php`
- `assets/css/style.css`
- `assets/js/chat.js`

Behavior:
- renders a floating button in the bottom-right corner
- uses custom icon if configured, otherwise shows default AI label/dot
- opens a responsive chat panel (id: `aiwoo-chat-panel`)
- launcher button has `aria-controls="aiwoo-chat-panel"` for accessibility
- shows:
  - company name in header
  - messages area
  - loading indicator
  - input field and send button
- sends chat requests via AJAX to `admin-ajax.php`
- appends AI responses dynamically
- mounts enquiry form HTML returned from backend when no product match is found

### 4. AJAX chat flow

File: `includes/class-aiwoo-assistant-ajax-controller.php`

AJAX actions:
- `ai_woo_assistant_chat`
- `ai_woo_assistant_enquiry`

Chat handler responsibilities (in order):
- verify plugin enabled
- detect and reject known bot/crawler User-Agents (empty UA also rejected)
- verify nonce using `check_ajax_referer`
- apply IP-based rate limiting (15 requests per sliding minute window)
- sanitize:
  - `message`
  - `history`
  - `pageContext`
- call `Chat_Service::generate_reply()`
- return JSON success/error payloads

Bot detection is performed by `is_bot_request()` which checks `HTTP_USER_AGENT` against a list of signatures: `bot`, `crawler`, `spider`, `slurp`, `curl/`, `wget/`, `python-`, `python/`, `scrapy`, `httpclient`, `go-http-client`, `java/`, `ruby/`, `perl/`, `libwww`, `okhttp`, `apache-httpclient`. An empty User-Agent string is also treated as a bot.

Enquiry handler responsibilities:
- verify plugin enabled
- verify nonce
- sanitize:
  - `name`
  - `phone`
  - `email`
  - `message`
- send email to WordPress admin email
- store enquiry as private `aiwoo_enquiry` post

## AI Layer

### Provider abstraction

Files:
- `includes/class-aiwoo-assistant-provider-interface.php`
- `includes/class-aiwoo-assistant-openai-provider.php`
- `includes/class-aiwoo-assistant-claude-provider.php`
- `includes/api-handler.php`

Current design:
- `Provider_Interface` defines `generate_response(array $payload)` — payload contains `instructions` (system) and `input` (user message + context)
- `OpenAI_Provider` — `POST https://api.openai.com/v1/responses`; auth via Bearer token; body: `{model, temperature, instructions, input}`
- `Claude_Provider` — `POST https://api.anthropic.com/v1/messages`; auth via `x-api-key` header + `anthropic-version: 2023-06-01`; body: `{model, max_tokens, temperature, system, messages}`
- `Gemini_Provider` — `POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}`; body: `{system_instruction, contents, generationConfig}`
- `includes/api-handler.php` exposes:
  - `make_ai_provider(Settings $settings)` — factory, switches on `provider` setting
  - `call_ai_model($message, $context)` — calls provider; `$context['settings']` used for DI

All three providers are fully functional. `sanitize_settings` validates provider against `['openai', 'claude', 'gemini']`.

### Current system prompt base

Default prompt is stored in plugin settings defaults:

```text
You are a professional customer support assistant for an eCommerce company.
Your role is to help users find products and answer queries naturally.

Rules:
- Always respond like a human sales assistant.
- Be polite, concise, and helpful.
- Recommend products when relevant.
- If no product available, ask for more details.
- Do not hallucinate products.
```

Additional runtime constraints are appended in `Chat_Service::build_instructions()`, including store name, description, currency, and anti-hallucination guidance.

## WooCommerce Integration

Files:
- `includes/woocommerce-handler.php`
- `includes/class-aiwoo-assistant-catalog-service.php`

Current behavior:
- detects current product context on single product pages via `wc_get_product(get_the_ID())`
- extracts relevant product data:
  - ID
  - name
  - price
  - regular price
  - sale price
  - SKU
  - permalink
  - stock status
  - short description (trimmed to 30 words to control token usage)
  - description (trimmed to 50 words)
  - categories
  - tags
  - visible attributes

### Product search logic

Current product search is implemented in `Catalog_Service::find_relevant_products()`.

Behavior:
- tokenizes the user message into keywords
- removes short/noisy tokens
- runs `WP_Query` against `post_type = product`
- uses WordPress search (`s`) per keyword
- includes current product ID first when present
- returns up to `max_context_products`

Important note:
- this is keyword-based search using WordPress search behavior, not semantic retrieval
- search hits depend on how WordPress/WooCommerce search indexes title/content

## Chat Decision Logic

File: `includes/class-aiwoo-assistant-chat-service.php`

### Decision order in `generate_reply()`

1. **Quick reply match** — intercepts before any catalog/AI call; returns immediately if matched
2. **No API key guard** — checks if the selected provider's API key is blank; if so, returns `no_match_text` + enquiry form without any AI or catalog call
3. **Route to MCP or legacy path**

### If matching products are found

Flow:
- build instructions from configured prompt + store metadata
- build input context with:
  - recent conversation (last 6 entries, HTML stripped, truncated to 1000 chars each)
  - current page context
  - relevant products
  - latest customer message
- call `call_ai_model()`
- format result as conversational HTML plus product cards

Returned response shape:
- `message`
- `html = true`
- `enquiry_form = false`
- `recommendations = [...]`

### If AI provider fails (no credits, API error, etc.)

Flow (both legacy and MCP paths):
- catch AI exception, log error server-side
- run catalog search with user message (MCP path does this on failure; legacy path already has products)
- if products found: return fallback text `"Here are some products that might match…"` + product cards
- if no products found: return enquiry form

This ensures the customer always sees useful results even when the AI is unavailable.

### If no matching products are found

Flow:
- do not call AI
- return hardcoded fallback text:
  - `We couldn't find an exact match. Please share more details.`
- return enquiry form HTML

Returned response shape:
- `message`
- `html = false`
- `enquiry_form = true`
- `enquiry_form_html`
- `recommendations = []`

## Admin Menu

File: `includes/class-aiwoo-assistant-admin-menu.php`

Top-level menu slug: `sellora-ai` | Icon: inline base64 SVG | Position: 58

`Admin_Menu` is instantiated via `Plugin::init_admin_menu()` hooked to `admin_menu` at **priority 1** (not `admin_init` — `admin_menu` fires before `admin_init` so menus would never register). The constructor's `add_action('admin_menu', ...)` at default priority 10 fires within the same hook execution.

Sub-pages:

| Label | Slug | Renderer |
|---|---|---|
| Chat History | `sellora-ai` | `render_chat_history()` |
| Enquiries | `sellora-ai-enquiries` | `render_enquiries()` |
| IP Blocklist | `sellora-ai-ip-blocklist` | `render_ip_blocklist()` |
| Settings | `ai-woo-assistant` | `Settings::render_settings_page()` |

`get_settings_hook()` — returns settings page hook suffix (used by Plugin for admin asset enqueueing).

`render_chat_history()`:
- reads `$_GET['session']` — if present, loads `admin/chat-session-detail-page.php` with messages for that session
- otherwise loads `admin/chat-history-page.php` with paginated session list (20/page) and filters: ip, name, date_from, date_to

`render_enquiries()`:
- queries `aiwoo_enquiry` CPT (private, latest first, 20/page)
- supports filters: name (LIKE on `_aiwoo_name`), email (LIKE on `_aiwoo_email`), date (exact date match)
- loads `admin/enquiries-page.php`

## IP Blocklist

File: `includes/class-aiwoo-assistant-ip-blocker.php`

### Storage

WordPress option `aiwoo_blocked_ips` (autoload = false). Plain array of strings, maximum 500 entries. Cleaned up in `uninstall.php`.

### Supported entry formats

| Format | Example |
|---|---|
| Exact IPv4 | `203.0.113.5` |
| Exact IPv6 | `2001:db8::1` |
| IPv4 CIDR | `203.0.113.0/24`, `10.0.0.0/8` |
| IPv6 CIDR | `2001:db8::/32` |

### Enforcement points (all server-side)

1. `Plugin::enqueue_assets()` — scripts/styles not enqueued for blocked IPs; widget receives no frontend code
2. `Plugin::render_widget_template()` — widget HTML not rendered for blocked IPs
3. `Ajax_Controller::handle_chat()` — returns 403 before bot check, before nonce verification
4. `Ajax_Controller::handle_enquiry()` — returns 403 before nonce verification

The check is always server-side. Blocked IPs receive no widget HTML, no JS, and no API response. There is nothing to bypass from the browser.

### IP source

`$_SERVER['REMOTE_ADDR']` only (the TCP peer address). `HTTP_X_FORWARDED_FOR` and similar headers are deliberately ignored to prevent header spoofing. If the site runs behind a trusted reverse proxy, configure real-IP passthrough at the web server / load-balancer level so that `REMOTE_ADDR` already contains the client IP before PHP runs.

### Admin UI

Sub-page slug: `sellora-ai-ip-blocklist`
Template: `admin/ip-blocklist-page.php`

Actions:
- `admin_post_aiwoo_add_blocked_ip` — validates entry, deduplicates, enforces max-500 cap, redirects with `aiwoo_ip_msg` query param
- `admin_post_aiwoo_delete_blocked_ip` — removes entry by value, redirects

Both actions require `manage_options` capability and `check_admin_referer`.

### Key methods

- `is_blocked($ip)` — public; returns bool; safe to call with any string (invalid IPs return false)
- `get_list()` — public; returns current array
- `get_visitor_ip()` — public static; reads `REMOTE_ADDR`
- `validate_entry($entry)` — returns `true` or `WP_Error`; checks exact IP or CIDR validity including prefix range
- `ipv4_in_cidr($ip, $subnet, $prefix)` — uses bitwise mask with `ip2long`
- `ipv6_in_cidr($ip, $subnet, $prefix)` — uses `inet_pton` binary comparison byte-by-byte

### AJAX security order (updated)

IP block check → bot UA check → nonce → rate limit → payload size check → sanitize

## Chat Logger

File: `includes/class-aiwoo-assistant-chat-logger.php`

DB table: `{$wpdb->prefix}aiwoo_chat_logs`

Schema:

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned AUTO_INCREMENT | PK |
| session_id | varchar(64) | indexed |
| ip_address | varchar(45) | indexed (20-char prefix) |
| customer_name | varchar(150) | indexed (50-char prefix); backfilled from enquiry |
| user_message | text | |
| ai_response | text | HTML stripped on insert |
| created_at | datetime | indexed |

Key methods:
- `create_table()` — static, uses `dbDelta`, safe to re-run; called on activation hook
- `maybe_create_table()` — static, checks `aiwoo_db_version` option before running
- `drop_table()` — static, called from `uninstall.php`
- `log($session_id, $ip, $user_message, $ai_response)` — silently swallows exceptions
- `backfill_customer_name($session_id, $name)` — updates all rows for a session when enquiry is submitted
- `get_sessions($filters, $per_page, $offset)` — grouped by session_id, returns aggregated rows
- `count_sessions($filters)` — for pagination
- `get_session_messages($session_id)` — chronological messages for detail view

## Admin Settings

File: `includes/class-aiwoo-assistant-settings.php`

Implemented with the WordPress Settings API.

### Stored option name

`ai_woo_assistant_settings`

### Current settings fields

**General tab**
- `enabled`, `max_message_length` (10–1000, default 200), `provider`, `openai_api_key`, `openai_model`, `temperature`, `max_context_products`

**Widget tab**
- `panel_title`, `panel_subtitle`, `company_logo`, `employee_photo`, `chat_icon`, `welcome_message`

**Appearance tab**
- `primary_color` (accent), `color_primary_hover`
- `color_surface`, `color_bg`, `color_border`, `color_text`, `color_text_soft`
- `color_header_bg`, `color_header_text`
- `color_user_bubble_bg`, `color_user_bubble_text`, `color_agent_bubble_bg`, `color_agent_bubble_text`
- `color_input_bg`, `color_input_text`, `color_send_bg`, `color_send_text`, `color_send_hover_bg`
- `color_loading_bg`, `color_loading_text` (typing indicator background and text)
- `color_counter_bg`, `color_counter_text` (character counter background and text)
- `color_panel_border` (`.aiwoo-panel` border; default `#000`)
- `color_header_border_bottom` (`.aiwoo-panel__header` bottom border; default = accent hover)

**AI & Prompt tab**
- `system_prompt`

All `color_*` fields: empty string = use CSS default. Sanitized with `sanitize_hex_color`.

### Current UI field types

- Enable widget: checkbox
- AI provider: dropdown (OpenAI only; Claude stub exists but is disabled in UI and forced to OpenAI in sanitize)
- OpenAI API key: password input
- OpenAI model: dropdown (gpt-5.4-mini, gpt-5.4, gpt-4.1-mini)
- Response temperature: number input (0–1)
- Catalog products in context: number input (1–10)
- Widget accent color: WordPress color picker
- Chat icon: media upload field
- Welcome message: textarea (sanitized with `sanitize_textarea_field`)
- Additional system prompt: textarea

### Admin assets

File: `assets/js/admin.js`

Behavior:
- initializes WordPress color picker
- opens media uploader for chat icon selection

## Frontend Data Contract

Localized object name: `AIWooAssistant`

Currently includes:
- `ajaxUrl`
- `nonce`
- `actions.chat`
- `actions.enquiry`
- UI strings (title, companyName, subtitle, placeholder, send, open, close, typing, error, welcome, emptyValidation, enquiry fields)
- `ui`:
  - `primaryColor`
  - `iconUrl`
  - `companyLogo`
  - `employeePhoto`
  - `faviconUrl` — always `AI_WOO_ASSISTANT_URL . 'assets/img/favicon.svg'`; used as assistant avatar fallback when `employeePhoto` is empty
- `storeContext`
  - `currencySymbol`
  - `pageUrl`
  - `product`
- `featureFlags.hasWooCommerce`
- `widgetStateKey`

## Enquiry Storage

Custom post type:
- `aiwoo_enquiry`

Registration:
- private
- no admin UI
- no public query

Saved meta:
- `_aiwoo_name`
- `_aiwoo_phone`
- `_aiwoo_email`

The enquiry body is stored as `post_content`.

## Security

### Security model

**Layer order for chat AJAX** (`handle_chat`):
1. Plugin-enabled check
2. IP blocklist (TCP peer `REMOTE_ADDR` only — not spoofable via headers)
3. Bot User-Agent detection (17 signatures + empty UA) — cheap short-circuit before nonce
4. Nonce verification (`check_ajax_referer`)
5. Fixed-window rate limit: 15 req / 60-second window per IP
6. Payload size guards (history ≤ 8 000 bytes, pageContext ≤ 4 000 bytes)
7. Message-length limit (auto-blocks IP on exceeded; configurable 10–1 000 chars)
8. Input sanitisation

**Layer order for enquiry AJAX** (`handle_enquiry`):
1. Plugin-enabled check
2. IP blocklist
3. Nonce verification
4. Fixed-window rate limit (shared with chat — 15 req / 60-second window)
5. Honeypot check (`aiwoo_hp` field must be empty; non-empty → silent success, nothing stored)
6. Input validation (name, email required; email format via `is_email()`)
7. Input sanitisation

### Implemented controls

- `defined( 'ABSPATH' ) || exit;` in all PHP files
- Bot User-Agent detection in `handle_chat()` — rejects empty UA and 17 known crawler signatures before nonce verification
- Nonce verification on AJAX handlers (`check_ajax_referer`)
- **Fixed-window rate limit** — 15 requests per 60-second window per IP; key encodes IP hash + current minute epoch so each window is independent (no TTL-reset drift)
- Rate limiting applied to **both** chat and enquiry endpoints
- **Honeypot field** on enquiry form — `aiwoo_hp` is hidden via CSS; bots that populate it receive a silent 200 success without any data being stored or emailed
- Sanitization of all inputs: settings, chat message, enquiry fields, page context, conversation history (HTML stripped with `wp_strip_all_tags`)
- API key never exposed to frontend (not included in `wp_localize_script` data)
- All admin page renderers guard with `current_user_can('manage_options')`
- All admin_post handlers guard with `current_user_can('manage_options')` + `check_admin_referer`
- Settings saved via `register_setting()` with `sanitize_settings()` callback
- Exception messages from AI providers **never forwarded to the browser**; a generic translated string is returned instead; real messages logged via `error_log()` only when `WP_DEBUG_LOG` is active
- CSV export sanitizes cells against formula injection (cells starting with `=`, `+`, `-`, `@`, tab, or CR are prefixed with a tab)
- All admin output uses `esc_html()`, `esc_attr()`, `esc_url()`; paginate links use `wp_kses_post()`
- `provider` setting validated against whitelist `['openai', 'claude', 'gemini']` in `sanitize_settings`
- `maybe_warn_if_woocommerce_missing` hooked on `admin_init` — runs only in admin context

## Performance and Token Efficiency

Currently implemented:
- frontend requests use AJAX
- frontend script loads in footer
- widget only loads when plugin is enabled
- product query uses:
  - `fields => ids`
  - `no_found_rows => true`
  - disabled meta/term cache updates in search queries
- `short_description` is trimmed to 30 words before being sent in AI context
- `description` is trimmed to 50 words
- history messages have HTML stripped before inclusion in AI prompt (prevents assistant HTML card markup from counting against token budget)
- history is capped at last 8 entries in `sanitize_history`, last 6 entries in `build_input`
- each history entry is truncated to 1000 characters

## Compatibility Notes

### Declared compatibility

- WordPress: plugin header says requires at least `6.4`
- PHP: plugin header says requires `8.0`
- WooCommerce: header includes compatibility notes

### Actual code style

The current code is broadly compatible with PHP 8.3.

## MCP Tool-Calling Architecture (session 15)

When `enable_mcp = yes`, the plugin switches from the legacy prompt-based path to an MCP-style tool-calling path.

### Legacy vs MCP flow

**Legacy (default):**
```
User message → Quick Reply check → Catalog_Service search → inject products into prompt → AI responds
```

**MCP:**
```
User message → Quick Reply check → AI receives message + tool definitions (no product data)
→ AI calls get_products / get_product_details / get_related_products as needed
→ MCP_Tools executes and returns results → AI responds with final text
→ Chat_Service renders product cards from fetched_products
```

### MCP tools

| Tool | Input | Output | Condition |
|---|---|---|---|
| `get_products` | query, limit | id, name, price, url, stock_status | Always available |
| `get_product_details` | product_id | name, price, attributes, description, sku, stock, url | Always available |
| `get_related_products` | product_id | upsells[], cross_sells[] | `enable_upsell = yes` |
| `get_user_context` | session_id (optional) | viewed_products, search_history, cart_items | `enable_personalization = yes` |

### Token optimisation

- No product data in initial prompt — AI fetches only what it needs
- Tool results return slim JSON (not full product objects)
- Transient cache (120s) prevents repeated DB queries within the same window
- Per-tool call cap (3/tool/request) prevents runaway loops
- Max 5 API round trips per chat turn

### Personalisation data flow

1. `chat.js` tracks viewed products and search keywords in `sessionStorage`
2. On each chat message, these are merged into the `pageContext` payload
3. `Chat_Service::sanitize_page_context()` sanitises `viewedProducts` and `searchHistory`
4. `MCP_Tools::set_request_context()` receives the sanitised context
5. When AI calls `get_user_context`, the tool returns viewed products, search history, and server-side cart items

### New settings (AI Intelligence tab)

| Setting | Default | Description |
|---|---|---|
| `enable_mcp` | `no` | Toggle MCP tool-calling mode |
| `mcp_max_products` | `5` | Max products per `get_products` call (1–10) |
| `enable_personalization` | `no` | Expose `get_user_context` tool |
| `enable_upsell` | `no` | Expose `get_related_products` tool |

### Files involved

- `includes/class-aiwoo-assistant-mcp-tools.php` — tool definitions, executor, caching
- `includes/class-aiwoo-assistant-chat-service.php` — MCP routing, instruction/message builders
- `includes/class-aiwoo-assistant-provider-interface.php` — `generate_with_tools()` contract
- `includes/class-aiwoo-assistant-openai-provider.php` — OpenAI function calling
- `includes/class-aiwoo-assistant-claude-provider.php` — Anthropic tool_use protocol
- `includes/class-aiwoo-assistant-gemini-provider.php` — Gemini functionCall protocol
- `includes/class-aiwoo-assistant-settings.php` — 4 new settings fields
- `admin/settings-page.php` — AI Intelligence tab UI
- `assets/js/chat.js` — personalisation tracking (viewedProducts, searchHistory)
- `includes/class-aiwoo-assistant-catalog-service.php` — `$limit` parameter added
- `includes/class-aiwoo-assistant-ajax-controller.php` — pageContext size limit raised to 6000

## Known Gaps / Caveats

1. ~~Rate limiter uses a sliding window (transient TTL resets on each request).~~ — **resolved (session 14)**. Now uses a fixed-window key that rotates every 60 seconds.

2. The current "no product found" behavior is deterministic and does not use AI, because the latest requested behavior explicitly changed that flow.

3. Enquiry storage is implemented via a hidden custom post type rather than a custom database table.

4. `call_ai_model()` creates a fresh `Settings` object internally instead of receiving dependency injection when no settings object is provided via context.

5. ~~`provider` selection supports `claude` in code, but Claude calls throw an exception.~~ — **resolved (session 5)**. All three providers (OpenAI, Claude, Gemini) are fully functional including MCP tool-calling. Provider dropdown validates against `['openai', 'claude', 'gemini']`.

6. The frontend persists recent messages to `sessionStorage`, which is useful for UX but means chat history is browser-session scoped only.

7. ~~There is no separate admin UI to view stored enquiries~~ — resolved. Enquiries admin page added at `admin/enquiries-page.php` with name/email/date filters and pagination.

8. Product matching uses keyword search, not embeddings or vector retrieval.

9. Bot detection is signature-based (User-Agent strings). A sophisticated bot using a real browser User-Agent would pass this check and rely only on rate limiting and nonce expiry for containment.

10. ~~No honeypot on enquiry form~~ — **resolved (session 14)**.

## Suggested Next Improvements

- ~~add admin UI for viewing/storing/exporting enquiries~~ — done
- improve product matching with weighted ranking or taxonomy-aware search
- ~~add provider credentials/settings for Claude before enabling that option~~ — done (session 5)
- ~~implement a true fixed-window rate limiter~~ — done
- consider moving `call_ai_model()` to a class-based service for stronger dependency injection
- ~~add a honeypot field to the enquiry form~~ — done
- add CSV export to enquiries and chat history admin pages
- ~~implement MCP tool-calling to reduce token usage~~ — done (session 15)
- add category/tag browsing tool for MCP mode
- add streaming support for tool-calling responses

## Validation Status

During implementation, the plugin files were syntax-checked with:
- `php -l` on the PHP files
- `node --check` on frontend/admin JS files

No live WordPress or WooCommerce runtime test is recorded in this repository context file.

## Change Log

### 2026-04-08 (session 20) — WordPress.org release hardening

**Security red flags removed:**
- `file_get_contents()` — was used in `Admin_Menu::register_menus()` to read `favicon.svg` for the menu icon. Replaced with `private const MENU_ICON_SVG` — a hardcoded SVG string on the class. `base64_encode( self::MENU_ICON_SVG )` is called at menu registration time. Zero file I/O, no scanner flags.
- SVG internal CSS class names renamed `.cls-1/.cls-2/.cls-3/.cls-4` → `.aiwoo-mi-a/.aiwoo-mi-b/.aiwoo-mi-c/.aiwoo-mi-d` to avoid potential class name collisions when the SVG is inlined.
- Confirmed zero `eval()`, `base64_decode`, `exec`, `shell_exec`, `passthru` across the entire codebase (grep scan result: no matches).

**`uninstall.php` completed:**
- Added `delete_option( 'aiwoo_ai_error_log_db_version' )` (was missing for AI error log table).
- Added `DROP TABLE IF EXISTS {prefix}aiwoo_ai_error_logs` (was missing).
- All 3 custom tables now fully cleaned on deletion: `aiwoo_chat_logs`, `aiwoo_quick_replies`, `aiwoo_ai_error_logs`.
- All options cleaned: `ai_woo_assistant_settings`, `aiwoo_blocked_ips`, `aiwoo_db_version`, `aiwoo_qr_db_version`, `aiwoo_qr_seeded`, `aiwoo_ai_error_log_db_version`.

**`readme.txt` rewritten for WordPress.org:**
- **Third-Party Services section** added (WordPress.org hard requirement for plugins calling external APIs). Discloses all three AI provider endpoints, privacy policies, and terms of use links: OpenAI (`api.openai.com/v1/responses`), Anthropic (`api.anthropic.com/v1/messages`), Google (`generativelanguage.googleapis.com/v1beta/...`).
- Short description ≤ 150 chars ✓
- Tags reduced to ≤ 5 (WordPress.org limit) ✓
- All new features documented in Description and Changelog (MCP, product cards, AI error log, branding, send button, info page, etc.)
- FAQ entries: GPL compatibility, personal data storage, Third-Party data, send button, Quick Replies, AI error log, product cards, MCP mode.
- Screenshots section with 9 entries.

**Files changed:** `includes/class-aiwoo-assistant-admin-menu.php`, `uninstall.php`, `readme.txt`

---

### 2026-04-08 (session 19) — Admin backend branding

**Admin page headings:**
- All 9 admin pages (`chat-history-page.php`, `chat-session-detail-page.php`, `enquiries-page.php`, `ip-blocklist-page.php`, `quick-replies-page.php`, `top-requests-page.php`, `ai-errors-page.php`, `info-page.php`, `settings-page.php`) updated: the `"Sellora AI — "` text prefix replaced with `<img src="logo.svg" style="height:28px">` using flex layout.
- `chat-session-detail-page.php`: "Back" link moved out of `<h1>` to a standalone `<a>` tag below the heading for cleaner markup.

**Admin sidebar menu icon:**
- `dashicons-format-chat` replaced with a base64-encoded data URI built from `private const MENU_ICON_SVG`.

**Admin topbar (`WP_Admin_Bar`) node:**
- `add_admin_bar_node()` adds a `sellora-ai-bar` node with `favicon.svg` icon + label "Sellora AI".
- Three sub-items: Chat History (`sellora-ai`), AI Error Log (`sellora-ai-errors`), Settings (`ai-woo-assistant`).
- Visible only to `manage_options` users; shown on both frontend and backend admin bars.
- Tiny CSS block (`<style>`) injected via `admin_head` + `wp_head` for icon sizing and alignment.
- Hook order: `admin_bar_menu` priority 100 (after WordPress core nodes).

**Files changed:** `admin/chat-history-page.php`, `admin/chat-session-detail-page.php`, `admin/enquiries-page.php`, `admin/ip-blocklist-page.php`, `admin/quick-replies-page.php`, `admin/top-requests-page.php`, `admin/ai-errors-page.php`, `admin/info-page.php`, `admin/settings-page.php`, `includes/class-aiwoo-assistant-admin-menu.php`

---

### 2026-04-08 (session 18) — UI refinements, product card controls, send button, branding, info page

**CSS fixes:**
- `.aiwoo-message p` — `line-height: 21px`
- `.aiwoo-product-card__desc` — `line-height: 14px`
- `.aiwoo-form` — border changed from `2px solid #000` to `1px solid var(--aiwoo-form-border)` (new CSS var, default `#000000`)

**Product card display toggles:**
- 5 new settings: `card_show_price`, `card_show_stock`, `card_show_image`, `card_show_desc`, `card_show_view_link` — all default `'no'`
- Settings exposed under Settings → Widget → Product Cards section
- `Catalog_Service::format_product()` now includes `image_url` field (`wp_get_attachment_image_url()` at `thumbnail` size)
- `Chat_Service::build_product_cards_html()` checks each setting; cards changed from `<a>` wrappers to `<div>` with stretched-link CSS pattern (`.aiwoo-product-card__title::after { position:absolute; inset:0 }` makes whole card clickable via title link; `.aiwoo-product-card__view` uses `z-index:1` to sit above stretched link)
- New CSS: `.aiwoo-product-card__image` (100% width, 90px height, object-fit:cover), `.aiwoo-product-card__view` (underline link, z-index:1)

**No-match fallback text configurable:**
- New `no_match_text` setting (default: `"We couldn't find an exact match. Please share more details."`)
- Used in `generate_reply_legacy()` (empty products) and `build_product_fallback_response()` (no products in fallback)
- Exposed in Settings → Widget tab

**Form border color setting:**
- New `color_form_border` setting → `--aiwoo-form-border` CSS var, emitted by `Plugin::build_color_css()`
- Exposed in Settings → Appearance → Input & Send Button section

**Send button disabled when empty:**
- New `updateSendButton()` in `chat.js` — disables when `input.value.trim() === ''` or `is-loading` class present
- Called on: `input` event, `setLoading()`, form `submit`, and initial setup

**SVG branding defaults:**
- `assets/img/favicon.svg` — used as default launcher icon (fallback when `chat_icon` setting empty)
- `assets/img/logo.svg` — used as default panel header logo (fallback when `company_logo` setting empty)
- Applied in `templates/chat-widget.php` via `AI_WOO_ASSISTANT_URL` constant; default inline SVG removed

**Plugin Guide page:**
- New admin page `Sellora AI → Plugin Guide` (`admin/info-page.php`)
- Covers: overview, AI providers table, widget settings, product cards, MCP mode, quick replies, IP blocklist, chat history & logs, enquiries, system prompt customisation
- Registered via `render_info()` in `Admin_Menu`; hook added to `get_all_hooks()`

**Files changed:** `assets/css/style.css`, `assets/js/chat.js`, `templates/chat-widget.php`, `includes/class-aiwoo-assistant-settings.php`, `includes/class-aiwoo-assistant-catalog-service.php`, `includes/class-aiwoo-assistant-chat-service.php`, `includes/class-aiwoo-assistant-plugin.php`, `includes/class-aiwoo-assistant-admin-menu.php`, `admin/settings-page.php` | **New:** `admin/info-page.php`

---

### 2026-04-08 (session 17) — AI Error Logger

**New DB table `{prefix}aiwoo_ai_error_logs`:**
- Schema: `id`, `session_id`, `ip_address`, `user_message`, `error_context` (varchar 20), `error_message`, `created_at`
- Versioned via `aiwoo_ai_error_log_db_version` option; created via `dbDelta`; dropped in `uninstall.php`
- Three `error_context` values: `ajax` (hard failure — user saw error), `mcp` (soft MCP fallback), `legacy` (soft legacy fallback)

**New class `AI_Error_Logger`:**
- `create_table()`, `maybe_create_table()`, `drop_table()` — same schema-management pattern as Chat_Logger
- `log($session_id, $ip_address, $user_message, $error_context, $error_message)` — silently swallowed
- `get_errors($per_page, $offset)` — latest first
- `count_errors()` — for pagination

**Wiring:**
- Injected into `Ajax_Controller`, `Chat_Service`, and `Admin_Menu` as constructor dependency
- `Chat_Service::generate_reply()` now accepts `$session_id` and `$ip_address` params (forwarded from `Ajax_Controller`) and passes to `generate_reply_mcp()` / `generate_reply_legacy()`
- `Ajax_Controller`: `$ip_address` hoisted before try block; `$this->ai_error_logger->log(...)` called in catch block (context: `ajax`)
- `Chat_Service` MCP catch: logs with context `mcp`; legacy catch: logs with context `legacy`
- `Plugin::__construct()`: calls `AI_Error_Logger::maybe_create_table()`; activation hook calls `AI_Error_Logger::create_table()`

**Admin page `Sellora AI → AI Error Log`:**
- Template: `admin/ai-errors-page.php`
- Type badges: red = "No Response", amber = "MCP Fallback" / "Legacy Fallback"
- Shows: time, type, IP, user message (truncated 120), error detail (truncated 300); 30/page

**Files changed:** `woocommerce-ai-chatbot-sellora.php`, `includes/class-aiwoo-assistant-plugin.php`, `includes/class-aiwoo-assistant-ajax-controller.php`, `includes/class-aiwoo-assistant-chat-service.php`, `includes/class-aiwoo-assistant-admin-menu.php` | **New:** `includes/class-aiwoo-assistant-ai-error-logger.php`, `admin/ai-errors-page.php`

---

### 2026-04-09 (session 20) — README.md overhaul

Full rewrite of `README.md` to match current state of the plugin. The previous README described only the initial 4-tab settings UI and 4 admin pages, missing every feature added in sessions 5–19.

**Sections updated:**
- **Intro** — added MCP mode, Quick Replies, personalisation, upsell, 8-page admin dashboard mention
- **Features** — added Two Operating Modes (legacy vs MCP), Personalisation & Upsell, Quick Replies, Top Requests Analytics, API Key Security, AI Error Log, honeypot, HPOS compatibility, square thumbnails, corner radius, configurable product card fields, AI failure fallback, token caps
- **Admin Settings** — tab count corrected 4 -> 5 (added AI Intelligence); colour picker count 17 -> 24
- **Requirements** — added "Tested up to" column (WP current, WC 9.0, PHP 8.4)
- **Admin Pages** — corrected from 4 sub-pages to 8 (Chat History, Enquiries, IP Blocklist, Quick Replies, Top Requests, AI Error Log, Plugin Guide, Settings)
- **Configuration** — added Step 5 "AI Intelligence"; colour customisation expanded (shape, panel borders, form borders, typing indicator, counter)
- **Security** — pipeline corrected: 10 steps with Quick Reply check before AI call; fixed-window rate limit (not sliding); pageContext limit 6000 (was 4000); added `max_tokens = 1024` note
- **Project Structure** — added 5 new classes, 4 new admin pages, `assets/img/` directory
- **Token efficiency** — rewritten: slim format, smarter history (6 stored, 4 used, 500 chars, first-sentence for assistant), pageUrl dropped, keyword-overlap product context, MCP mode, all provider caps, Quick Reply bypass
- **Runtime efficiency** — new subsection documenting lazy loading (3 objects on frontend vs 10 before)
- **Changelog 1.0.0** — rewritten as full feature list grouped by theme

**Memory updates:**
- `project_overview.md` — corrected stale `readme.txt` reference (file doesn't exist; only `README.md`). Added note that `readme.txt` needs to be created before WordPress.org submission.

**Files changed:** `README.md`

---

### 2026-04-09 (session 19) — Token reduction & lazy loading

Applied all fixes from `prompt_file.md` (Parts 1A–1E, 2A–2B). Parts 2C, 2D, 2E, 2F verified as already correct.

**1A — System prompt trimmed (~50% shorter)**
- `build_instructions()`: 6 lines -> 2 (merged 3 anti-hallucination rules, dropped store description).
- `build_instructions_mcp()`: 5 lines -> 2 (merged tool-use + recommendation rules).

**1B — Product context shrunk**
- `Catalog_Service::format_product()`: dropped `sku`, `tags`, `categories`. `sale_price` + `regular_price` now included only when actually on sale. New `clean_text()` helper collapses whitespace and decodes HTML entities in `short_description` / `description`.
- `MCP_Tools::tool_get_products()`: omits `stock_status` when "instock" (assumed default). Key renamed `stock_status` -> `stock` when present.
- `MCP_Tools::tool_get_product_details()`: dropped `sku`. `stock` and `sale_price`/`regular_price` conditionally included.

**1C — Smarter history**
- `sanitize_history()`: cap reduced from -8 to -6 entries, each 1000 -> 500 chars. New first-sentence extraction for `assistant` role (regex strips product card text and long explanations).
- `build_input()` / `build_messages_mcp()`: history slice reduced from -6 to -4 entries. MCP truncation cap also 1000 -> 500.

**1D — Page context**
- `pageUrl` dropped from both `build_input()` and `build_messages_mcp()` (zero AI value).
- Current product context only included when the user message contains a keyword from the product name (>=3 chars).
- New `is_product_referenced()` helper performs the keyword-overlap check.
- In legacy path, product context is sent as slim `{name, price}` only (not full product JSON).
- In MCP path, product context is a single-line `Viewing: {name}` prefix.

**1E — Temperature admin notice**
- New `Plugin::maybe_temperature_notice()` — dismissible info notice shown only on Sellora AI admin pages when `temperature > 0.5`, suggesting 0.3 for tighter responses.
- Hooked on `admin_notices` inside the `is_admin()` block.

**2A — Lazy loading refactor**
- `Plugin::__construct()` no longer instantiates the full service graph. Only `Settings` and `IP_Blocker` are eager (needed on every request for widget render + block checks).
- New private getters `get_catalog_service()`, `get_ai_error_logger()`, `get_quick_reply_service()`, `get_chat_logger()`, `get_mcp_tools()`, `get_chat_service()`, `get_ajax_controller()` — each null-check lazy-init.
- AJAX hooks registered directly on `Plugin` (`handle_ajax_chat`, `handle_ajax_enquiry`) which lazy-init `Ajax_Controller` on first call.
- `Ajax_Controller::__construct()` no longer calls `add_action()` for AJAX hooks (now done by Plugin).
- `Admin_Menu` instantiated lazily on `admin_init` (priority 5) via `Plugin::init_admin_menu()`, only when `is_admin()`.
- Admin bar methods (`add_admin_bar_node`, `render_admin_bar_styles`) moved from `Admin_Menu` to `Plugin` so they work on the frontend without forcing `Admin_Menu` and its service dependencies to instantiate.
- `enqueue_assets()`: `$this->catalog_service->get_current_product_context()` changed to `$this->get_catalog_service()->get_current_product_context()`.
- `enqueue_admin_assets()`: null guard added for `$this->admin_menu`.
- **Frontend page load with widget:** 10 objects instantiated before -> 3 now (Settings, IP_Blocker, Catalog_Service).
- **Frontend page load, widget disabled/blocked:** 10 -> 2 objects.
- **AJAX chat request:** same chain as before, just built lazily.
- **Admin page:** Settings + IP_Blocker + Admin_Menu + its deps (Chat_Logger, Quick_Reply_Service, AI_Error_Logger).

**2B — Quick Reply cache TTL**
- `Quick_Reply_Service::CACHE_TTL` raised from 300s to 3600s. Rules rarely change and invalidation on insert/update/delete is already in place (confirmed in `insert()`, `update()`, `delete()`).

**Already correct (verified, no changes):**
- 2C: `WP_Query` in `find_relevant_products()` already has `fields=>ids`, `no_found_rows=>true`, `update_post_meta_cache=>false`, `update_post_term_cache=>false`, `posts_per_page=>$limit`.
- 2D: All four MCP tools verified — `get_products` (md5 key), `get_product_details` and `get_related_products` (per-ID keys), `get_user_context` (correctly not cached since it reads per-request state).
- 2E: `Chat_Logger::log()` uses `$wpdb->insert()`. Schema check is in `Plugin::__construct()` via `maybe_create_table()`, not per log call.
- 2F: `enqueue_admin_assets()` already early-returns unless `$hook === get_settings_hook()`.

**Estimated token savings (rough):**
- System prompt trim: ~50% fewer prompt tokens per request (-150 tokens avg)
- Product context shrink (slim format + whitespace collapse): ~30% fewer product-data tokens
- History trim (6->4 entries, 1000->500 chars, first-sentence for assistant): ~60% fewer history tokens
- Page context drop: ~20–30 tokens saved per request
- Combined: ~35–50% token reduction on a typical multi-turn chat

**Files changed:** `includes/class-aiwoo-assistant-chat-service.php`, `class-aiwoo-assistant-catalog-service.php`, `class-aiwoo-assistant-mcp-tools.php`, `class-aiwoo-assistant-plugin.php`, `class-aiwoo-assistant-ajax-controller.php`, `class-aiwoo-assistant-admin-menu.php`, `class-aiwoo-assistant-quick-reply-service.php`

**Syntax verification:** All PHP files pass `php -l`.

---

### 2026-04-09 (session 18) — Security audit fixes: token caps & API key masking

Full three-pillar audit (Security, Performance, WordPress Standards) performed. Five priority fixes applied:

**Fix 1 — OpenAI `max_output_tokens` cap (Responses API)**
- `class-aiwoo-assistant-openai-provider.php` `generate_response()`: added `'max_output_tokens' => 1024` to request body. Prevents unbounded token spend from long AI responses.

**Fix 2 — OpenAI `max_tokens` cap (Chat Completions API / MCP mode)**
- `class-aiwoo-assistant-openai-provider.php` `generate_with_tools()`: added `'max_tokens' => 1024` to request body.

**Fix 3 — Gemini `maxOutputTokens` cap (both paths)**
- `class-aiwoo-assistant-gemini-provider.php` `generate_response()`: added `'maxOutputTokens' => 1024` to `generationConfig`.
- `class-aiwoo-assistant-gemini-provider.php` `generate_with_tools()`: added `'maxOutputTokens' => 1024` to `generationConfig`.

**Fix 4 — API key masking in settings form**
- All three API key fields (OpenAI, Claude, Gemini) no longer render the raw key value in the HTML. Instead: `value=""` with a `placeholder` showing masked key (e.g. `sk-p****...xY4z`).
- New helper `Settings::mask_api_key()` — shows first 4 + last 4 characters, middle replaced with asterisks.
- `sanitize_settings()` updated: blank API key input preserves the existing saved key (no longer overwrites with empty string on save).
- Hint text "Key is saved. Leave blank to keep the current key" shown when a key exists.

**Fix 5 — POT file generation** (documented, not code change)
- Noted for build process: `wp i18n make-pot . languages/ai-woocommerce-assistant.pot`.

**Files changed:** `includes/class-aiwoo-assistant-openai-provider.php`, `includes/class-aiwoo-assistant-gemini-provider.php`, `includes/class-aiwoo-assistant-settings.php`

---

### 2026-04-08 (session 16) — AI failure fallback + chat_placeholder setting

**AI failure graceful fallback:**
- **Before:** When the AI provider failed (no credits, bad API key, network error), the user saw a dead-end error: "The assistant is temporarily unavailable. Please try again."
- **After:** Both legacy and MCP paths catch AI exceptions and fall back to catalog search. If products are found, the user sees "Here are some products that might match what you're looking for:" with product cards. If no products are found, the enquiry form is shown. Errors are still logged server-side.
- New private method `Chat_Service::build_product_fallback_response()` handles the fallback for both paths.

**Separate `chat_placeholder` setting:**
- **Before:** Panel header subtitle and chat input placeholder were conflated — both used the same hardcoded text.
- **After:** New `chat_placeholder` setting (default: `"Ask about products…"`). Editable under Settings → Widget tab, independent of `panel_subtitle`. Used in the widget template `<textarea placeholder>` and the localized JS `AIWooAssistant.strings.placeholder`.

### 2026-04-08 (session 15) — MCP tool-calling architecture

Full implementation of MCP-style tool-calling to replace the prompt-based product injection pattern. Nine-part scope:

**Part 1 — MCP Tool Layer:**
- New class `includes/class-aiwoo-assistant-mcp-tools.php` (449 lines) with 4 tools: `get_products`, `get_product_details`, `get_related_products`, `get_user_context`.
- Each tool validates/sanitises inputs, caches results via transients (120s TTL), and enforces per-tool call caps (3/tool/request).
- `get_products` reuses `Catalog_Service::find_relevant_products()` (new `$limit` param added).
- `get_product_details` fetches full product data including visible attributes via `wc_get_product()`.
- `get_related_products` uses `$product->get_upsell_ids()` and `$product->get_cross_sell_ids()`.
- `get_user_context` returns viewed products + search history (from frontend `sessionStorage`) + server-side WC cart items.

**Part 2 — AI Tool-Calling Integration:**
- `Provider_Interface` — added `generate_with_tools(array $payload, array $tools, callable $tool_executor)` method.
- All 3 providers implement the tool-calling loop (max 5 rounds):
  - **OpenAI:** Chat Completions `tools` + `tool_choice: auto`, `role: tool` for results.
  - **Claude:** Messages API `tools` with `input_schema`, `tool_use` stop_reason, `tool_result` blocks in user turn.
  - **Gemini:** `function_declarations`, `functionCall` parts, `functionResponse` parts.
- `Chat_Service` — new `generate_reply_mcp()` method with dedicated `build_instructions_mcp()` (no product data — AI uses tools) and `build_messages_mcp()` (history + lightweight page context). Routes via `enable_mcp` setting. Legacy path extracted to `generate_reply_legacy()` (unchanged behaviour).
- Product card rendering extracted into shared `build_product_cards_html()` used by both paths.

**Part 3 — Product-Aware Responses:**
- MCP system instructions include anti-hallucination guidance: "Never invent or guess product details — rely only on tool results."
- Tool results include price, stock status, URL — AI can cite real data.

**Part 4 — Personalised Recommendations:**
- `chat.js` — new `sessionStorage` tracking: `aiwoo_viewed_products` (auto-records on product pages), `aiwoo_search_history` (records each chat message). Both capped at 10 items, deduplicated.
- `pageContext` payload now includes `viewedProducts` and `searchHistory` arrays.
- `Chat_Service::sanitize_page_context()` — sanitises `viewedProducts` (array of {id, name}, max 10) and `searchHistory` (array of strings, max 10).
- `MCP_Tools::set_request_context()` stores sanitised context; `get_user_context` tool reads it.
- Clear chat button also clears personalisation storage.

**Part 5 — Upsell & Cross-sell Intelligence:**
- `get_related_products` tool conditionally exposed when `enable_upsell = yes`.
- Returns up to 4 upsell + 4 cross-sell products (id, name, price, url, stock_status).
- AI can suggest "You may also like…" and "Customers also bought…" items.

**Part 6 — Performance:**
- Tool results are slim JSON (not full WC product objects).
- Transient caching (120s) per query/product.
- `MAX_CALLS_PER_TOOL = 3` prevents repeated queries.
- Max 5 API round trips per turn.
- `pageContext` size limit raised from 4000 to 6000 bytes to accommodate personalisation fields.

**Part 7 — Security:**
- All tool inputs sanitised with `sanitize_text_field()`, `absint()`.
- Product IDs validated; non-existent products return error.
- Unknown tool names rejected via `sanitize_key()`.
- No sensitive data exposed in tool results.

**Part 8 — Settings (Admin):**
- 4 new settings: `enable_mcp` (checkbox), `mcp_max_products` (1–10), `enable_personalization` (checkbox), `enable_upsell` (checkbox).
- New "AI Intelligence" tab in `admin/settings-page.php` with MCP, Personalisation, and Upsell sections.
- Defaults: all disabled (`no`), `mcp_max_products = 5`.
- Sanitisation in `Settings::sanitize_settings()`.

**Part 9 — Wiring:**
- `woocommerce-ai-chatbot-sellora.php` — requires new MCP_Tools class.
- `Plugin::__construct()` — instantiates `MCP_Tools`, passes to `Chat_Service`.
- `Catalog_Service::find_relevant_products()` — new optional `$limit` parameter (used by MCP tool, clamped 1–10).

**Files changed:** 12 modified + 1 new (830 insertions, 27 deletions).
**Syntax verification:** All PHP files pass `php -l`, JS passes `node --check`.

### 2026-04-08 (session 14) — Security audit & hardening

Full security audit performed. Six issues resolved:

**H1 — Exception message leakage (High)**
- **Before:** `wp_send_json_error(['message' => $exception->getMessage()], 500)` — raw provider errors (including WP_Error network strings like "cURL error 6: …" and API error text like "Incorrect API key…") were forwarded to the browser.
- **After:** `Ajax_Controller::handle_chat()` catch block returns a generic translated string. Full exception message is written to the PHP error log only when `WP_DEBUG_LOG` is true.

**H2 — No rate limit on enquiry endpoint (High)**
- **Before:** `handle_enquiry()` had no rate limiting; an attacker could send thousands of enquiry submissions per minute, flooding admin email.
- **After:** `rate_limit_ok()` is called inside `handle_enquiry()` (same 15/60 s limit as chat).

**H3 — Sliding-window rate limiter TTL reset (High)**
- **Before:** `set_transient($key, $count + 1, MINUTE_IN_SECONDS)` reset the TTL on every request. An attacker staying at 14 req/min sustained the window indefinitely.
- **After:** Key now encodes `floor(time() / 60)` (current 60-second epoch). Each window gets a fresh independent key with a 90 s TTL. Windows cannot bleed into each other.

**M1 — CSV formula injection in Top Requests export (Medium)**
- **Before:** `fputcsv()` wrote `$row->query` and `$row->last_response` verbatim. Cells beginning with `=`, `+`, `-`, `@` are interpreted as formulas by spreadsheet software.
- **After:** `Admin_Menu::sanitize_csv_cell()` prefixes dangerous-leading cells with a tab character. Applied to query and response preview columns.

**M2 — No honeypot on enquiry form (Medium)**
- **Before:** Enquiry form had no spam-bot protection beyond nonce.
- **After:** `Chat_Service::get_enquiry_form_html()` emits a hidden `<input name="aiwoo_hp">` (off-screen via CSS). `handle_enquiry()` checks it before any DB/email work; non-empty value → silent success, nothing stored. `chat.js` `handleEnquirySubmit()` sends `aiwoo_hp` in the payload (value is always empty for real users).

**L1 — LIMIT/OFFSET not wrapped in `$wpdb->prepare()` (Low)**
- **Before:** `get_sessions()` interpolated `$per_page` and `$offset` directly into the SQL string (both were `absint()`-safe but not prepared).
- **After:** `$wpdb->prepare()` wraps the full query with `%d` placeholders for LIMIT and OFFSET.

### 2026-04-08 (session 13)

- **Top Requests analytics page** (`sellora-ai-top-requests`) — aggregates `aiwoo_chat_logs` by normalized user message, shows frequency, response preview, type badge, and "Save as Quick Reply" inline form.
  - Renderer `render_top_requests()` in `Admin_Menu`: builds WHERE (date filter), HAVING (search), fetches up to 500 rows via `GROUP BY LOWER(TRIM(user_message)) ORDER BY total DESC`, applies response-type filter in PHP using `Quick_Reply_Service::get_response_set()` hash set, paginates 20/page.
  - Response type detection: exact match of `last_response` against all active QR responses → "Quick Reply" or "AI Response" badge. 🔥 Popular badge when count ≥ 100.
  - Inline "Save as Quick Reply" form: toggle show/hide per row via vanilla JS. Pre-fills keywords (from query) and response (from last_response). POSTs to `admin_post_aiwoo_save_quick_reply_from_ai`.
  - `handle_save_from_ai()` in `Quick_Reply_Service`: validates, calls `keyword_exists()` for duplicate check, auto-generates title from first 5 words of source query, inserts via `insert()`.
  - `keyword_exists()`: loads all active rule keywords from DB, builds flat hash set, checks each input keyword.
  - `get_response_set()`: returns `array<string, true>` of all active QR response strings for O(1) type detection.
  - Filters: search (HAVING LIKE), response type (PHP), date (last 7/30 days or all time).
  - CSV export: nonce-protected `?export=csv` parameter streams up to 5000 rows.
  - Template: `admin/top-requests-page.php`.

### 2026-04-08 (session 12)

- **Quick Reply seed data expanded** — 50 additional default rules added to `insert_defaults()` (total 69). Categories: Product Discovery (new arrivals, trending, best seller, compare, which is better), Pricing & Offers (any offer, best price, price drop, emi, no cost emi), Delivery (fast delivery, same day, charges, international, delayed), Order & Account (cancel, change address, modify, invoice, login problem), Returns (return status, refund status, damaged product, wrong item, missing item), Product Info (specs, size, color, material, brand), Trust (genuine, quality, reviews, rating, safe payment), Conversion (should i buy, worth it, recommend me, gift, combo), Support (callback, whatsapp, email support, working hours, location), Fallback (confused, help, not sure, show options, browse). Priorities assigned by business impact: critical issues 82–85, order management 78–83, high-intent 70–75, discovery/offers 63–73, info/engagement 57–67.

### 2026-04-08 (session 11)

- **Quick Reply default seeding** — 19 predefined rules auto-inserted when the table is empty.
  - `seed_on_activation()` (static, no option guard): called from activation hook after `create_table()`. Counts rows; if 0, inserts all defaults and sets `aiwoo_qr_seeded` option. Safe on re-activation (skips if data exists).
  - `maybe_seed_defaults()` (static, option-guarded): called from `Plugin::__construct()` on every request. Reads autoloaded option `aiwoo_qr_seeded`; returns immediately if '1'. Only hits DB (SHOW TABLES + COUNT) when option is absent. Sets option after first check regardless of insert path.
  - `insert_defaults()` (private static): inserts all 19 rules via `$wpdb->insert()`, then flushes `aiwoo_quick_replies_cache` transient.
  - `drop_table()` updated to also delete `aiwoo_qr_seeded` option.
  - `uninstall.php` updated to delete `aiwoo_qr_seeded`.
  - Default rules cover: Greeting (100), Small Talk (90), Order Tracking (85), Complaint (85), Product Search (80), COD (75), Returns (75), Price (70), Budget (70), Stock (70), Delivery (70), Recommendations (70), Support (65), Bulk (65), Discounts (65), Warranty (60), Payment (60), Thank You (50), Goodbye (50).

### 2026-04-08 (session 10)

- **Quick Reply System** — rule-based keyword matching that intercepts messages before any catalog search or AI call.
  - New DB table `{prefix}aiwoo_quick_replies`: id, title, keywords (CSV), response, match_type (contains/exact), priority, status, created_at. Created via `dbDelta`, versioned with `aiwoo_qr_db_version` option.
  - New class `includes/class-aiwoo-assistant-quick-reply-service.php` (`Quick_Reply_Service`): `find_match()` normalises message to lowercase/trim, iterates active rules ordered by priority DESC, splits CSV keywords, tests `str_contains` or `===` per match_type. Returns first match response or null. Rules cached via transient `aiwoo_quick_replies_cache` (300s). Cache flushed on any insert/update/delete. Exposes `handle_save` and `handle_delete` as `admin_post_*` action handlers.
  - `Chat_Service` — injected with `Quick_Reply_Service` via constructor. `generate_reply()` calls `find_match()` immediately after sanitisation; if non-null, returns early with `html=false, enquiry_form=false, recommendations=[]`.
  - `Admin_Menu` — accepts `Quick_Reply_Service` as 4th constructor arg. New submenu `sellora-ai-quick-replies` → `render_quick_replies()`.
  - New admin template `admin/quick-replies-page.php`: list view with Title/Keywords/Match type/Response preview/Priority/Status/Edit+Delete actions; add/edit form with all fields + nonce + capability check. Add = `?action=add`, Edit = `?action=edit&id=N`.
  - `woocommerce-ai-chatbot-sellora.php` — require new class, activation hook creates both tables.
  - `uninstall.php` — drops `aiwoo_quick_replies` table, deletes `aiwoo_qr_db_version` option and transient.

### 2026-04-10 (session 22)

- **Auto-open delay** — new `auto_open_delay` setting (string, default `''` = disabled). Integer 1–300 stored as a string; empty string = feature off.
  - `Settings::$defaults` — added `'auto_open_delay' => ''`.
  - `Settings::sanitize_settings()` — if input is blank, stores `''`; otherwise `absint()` clamped 1–300 stored as string.
  - `Settings::render_field()` — new `case 'auto_open_delay'`: number input (`min=1 max=300 style="width:80px"`), labeled in seconds with description.
  - `admin/settings-page.php` — new row added to Widget tab after "Welcome message".
  - `includes/class-aiwoo-assistant-plugin.php` — `wp_localize_script` `settings` array now includes `'autoOpenDelay'` (integer, `0` when disabled).
  - `assets/js/chat.js` — after `setOpen(state.isOpen)`, reads `config.settings.autoOpenDelay`. If truthy and the widget is not already open, schedules `setTimeout(() => { if (!state.isOpen) setOpen(true); }, delay * 1000)`. The inner guard prevents re-opening if the user manually closes before the timer fires.

### 2026-04-10 (session 21)

- **Admin menu bug fix** — `Plugin::init_admin_menu()` was hooked to `admin_init` (priority 5). `admin_menu` fires before `admin_init` in WordPress, so `Admin_Menu::register_menus()` was never called — all menu pages were missing. Fixed by hooking `init_admin_menu` to `admin_menu` at priority 1 instead. The constructor's `add_action('admin_menu', ...)` at default priority 10 now fires correctly within the same hook execution.
- **CSS: `.aiwoo-widget section`** — added `padding: 0` rule.
- **CSS: `.aiwoo-panel__logo`** — added `color: #ffffff` default.
- **CSS: `--aiwoo-primary` default** changed from `#9a162d` to `#7310ec`.
- **CSS: `--aiwoo-primary-dark`** changed from hardcoded `#7d1125` to `color-mix(in srgb, var(--aiwoo-primary) 80%, black)` — auto-darkens whatever primary color is configured.
- **CSS: `--aiwoo-primary-deep`** changed from hardcoded `#650e1d` to `color-mix(in srgb, var(--aiwoo-primary) 65%, black)` — even darker auto-derived shade.
- **Assistant avatar fallback** — `createAvatar('assistant')` in `chat.js` now always renders an `<img>`. Uses `config.ui.employeePhoto` if set, falls back to `config.ui.faviconUrl` (`assets/img/favicon.svg`). `faviconUrl` added to the PHP `wp_localize_script` `ui` array.
- **No API key → rules-based only** — `Chat_Service::generate_reply()` now checks (after quick-reply match) whether the active provider's API key is blank. If blank, returns `no_match_text` + enquiry form immediately, skipping all catalog and AI calls.
- **Loading indicator order fix** — in `handleSend()`, `setLoading(false)` is now called *before* `addMessage()` in both try and catch branches. Removed the `finally` block. Loading indicator disappears before the message renders, not after.
- **Auto-focus after response** — after the try/catch in `handleSend()`, `elements.input.focus()` is called unconditionally so the user can type again immediately after every reply.

### 2026-04-08 (session 9)

- **Corner radius setting** — new `border_radius` setting (integer 0–24, default `0`). Outputs `--aiwoo-radius: Npx` CSS variable via `build_color_css()`. Applied to `.aiwoo-panel`, `.aiwoo-message--assistant` (three corners), `.aiwoo-message--user` (three corners), `.aiwoo-enquiry`. `0` = sharp (current default), `24` = fully rounded. Exposed in Appearance tab under new "Shape" section.

### 2026-04-08 (session 8)

- **CSS tweaks** — `.aiwoo-enquiry` added `margin: 20px 0px`. `.aiwoo-enquiry__title` added `line-height: 20px`. `.aiwoo-enquiry-form` added `margin-top: 15px`. `.aiwoo-enquiry-form textarea` added `resize: none`. `.aiwoo-panel__header h2` changed to `font-weight: 500; font-size: 16px`.

### 2026-04-08 (session 7)

- **CSS tweaks** — Removed `border-right` from `.aiwoo-input` (and its focus rule). Added `margin-right: 10px` to `.aiwoo-send`. Removed box-shadow on `.aiwoo-widget.is-open .aiwoo-panel`. Set `.aiwoo-char-counter` padding to `5px 5px 5px`. Set `.aiwoo-panel__header p` `line-height: 20px`. Removed `text-decoration` from `.aiwoo-clear` button.
- **Form background color setting** — new `color_form_bg` setting (default `#ffffff`) maps to `--aiwoo-form-bg` CSS variable used on `.aiwoo-form`. Exposed in Appearance tab under "Input & Send Button". Wired through settings defaults, sanitize, placeholder_map, `build_color_css`, and settings-page.php.

### 2026-04-08 (session 6)

- **Clear chat button** — trash-icon button (`.aiwoo-clear`) added to widget header left of the close button (`chat-widget.php`). On click, clears `state.messages`, removes both `storageKey` and `sessionKey` from `sessionStorage`, and re-renders the welcome message. Next message creates a fresh session ID — no prior history sent to AI, saving tokens.
- **Message bubble alignment fix** — short messages like "hi" were overflowing the bubble. Root cause: `.aiwoo-message-row--user` used `align-self: flex-end` which shrunk the row to content width, making the `max-width: calc(84% - 35px)` on the bubble a percentage of a tiny row. Fix: replaced `align-self` with `width: 100%` on all rows and `justify-content: flex-start/flex-end` per role so percentage max-width is correctly relative to the full messages container.
- **6 new color settings** — `color_loading_bg`, `color_loading_text`, `color_counter_bg`, `color_counter_text`, `color_panel_border`, `color_header_border_bottom`. Each maps to a new CSS custom property; injected via `build_color_css()` in `Plugin`. Registered as admin fields in `Settings::register_settings()`.
- **Input height** — `.aiwoo-input` `min-height` changed from `52px` to `75px`.

### 2026-04-07 (session 5)

- **Claude provider** (`class-aiwoo-assistant-claude-provider.php`) — rewritten from stub to full implementation. Uses Anthropic Messages API (`POST /v1/messages`). Headers: `x-api-key`, `anthropic-version: 2023-06-01`. Body: `model`, `max_tokens: 1024`, `temperature`, `system` (instructions), `messages[{role:user, content:input}]`. Validates model against allowed list; defaults to `claude-sonnet-4-6`.
- **Gemini provider** (`class-aiwoo-assistant-gemini-provider.php`) — new class. Uses Google Generative Language API (`POST /v1beta/models/{model}:generateContent?key={key}`). Body: `system_instruction.parts`, `contents[{role:user, parts}]`, `generationConfig.temperature`. Surfaces `finishReason` on non-STOP empty responses. Validates model; defaults to `gemini-2.0-flash`.
- **`api-handler.php`** — added Gemini require and `case 'gemini'` to `make_ai_provider()`. Claude now receives Settings in constructor.
- **Settings** — added `claude_api_key`, `claude_model`, `gemini_api_key`, `gemini_model` defaults + sanitize + `render_field` cases. Removed `provider` force-to-`openai`; now validates against `['openai', 'claude', 'gemini']`. Added `normalize_claude_model()` and `normalize_gemini_model()` helpers.
- **`admin/settings-page.php`** — provider credential/model rows now carry `data-aiwoo-provider` attribute; JS shows only the rows matching the active provider.
- **`admin.js`** — added `updateProviderRows()` function that shows/hides `[data-aiwoo-provider]` rows on page load and on provider dropdown change.

### 2026-04-07 (session 4)

- **Message length limit** — new setting `max_message_length` (10–1000, default 200). Frontend enforces via `maxlength` attribute + live character counter (`.aiwoo-char-counter` div injected by JS). Backend auto-blocks the sender IP via `IP_Blocker::add()` when the raw message exceeds the limit, then returns a generic 413. Counter turns amber at 85% of limit, red at 100%.
- **Color customization** — 17 new `color_*` settings, each maps to a CSS custom property on `.aiwoo-widget`. New `Plugin::build_color_css()` outputs overrides via `wp_add_inline_style`. Empty = CSS default. CSS file updated: all hard-coded colour values replaced with CSS variables; new variables added for header, bubbles, send button, input.
- **Settings tabs** — `admin/settings-page.php` rewritten with 4 tabs (General, Widget, Appearance, AI & Prompt) using WordPress `.nav-tab` classes. `Settings::render_settings_page()` now passes `$settings` to template. Active tab persisted via `localStorage`. `admin.js` updated to handle tab switching.
- **AJAX security order updated** — message length check (+ auto-block) inserted between rate-limit check and sanitize.
- `AIWooAssistant.settings.maxMessageLength` added to localized JS object.
- Removed manual `root.style.setProperty('--aiwoo-primary', ...)` from `chat.js` — handled entirely by PHP inline style now.

### 2026-04-07 (session 3)

- Added `IP_Blocker` class (`includes/class-aiwoo-assistant-ip-blocker.php`) — validates and stores exact IPs and CIDR ranges (IPv4 + IPv6); handles `admin_post_aiwoo_add_blocked_ip` and `admin_post_aiwoo_delete_blocked_ip`; IPv6 CIDR uses `inet_pton` binary comparison; IPv4 CIDR uses `ip2long` bitwise mask
- Added `admin/ip-blocklist-page.php` — admin table with add form and per-row delete; status notices via `aiwoo_ip_msg` query param; uses `wp_nonce_field` / `check_admin_referer`; `confirm()` before delete
- Updated `Admin_Menu` — added "IP Blocklist" submenu (slug `sellora-ai-ip-blocklist`); constructor now accepts `IP_Blocker`; `render_ip_blocklist()` method passes `$ip_blocker` to template
- Updated `Ajax_Controller` — `IP_Blocker` injected via constructor; IP block check added at top of `handle_chat()` and `handle_enquiry()` before bot detection and nonce
- Updated `Plugin` — instantiates `IP_Blocker`; passes to `Ajax_Controller` and `Admin_Menu`; IP block check added in `enqueue_assets()` and `render_widget_template()` — blocked IPs receive no widget HTML and no JS
- Updated `woocommerce-ai-chatbot-sellora.php` — added require for new IP_Blocker class
- Updated `uninstall.php` — `delete_option('aiwoo_blocked_ips')` on plugin deletion
- Updated `Project_context.md` and `.claude/memory.md` to document all above

### 2026-04-07 (session 2)

- Added `Chat_Logger` class — custom DB table `{prefix}aiwoo_chat_logs` with session_id, ip_address, customer_name, user_message, ai_response, created_at; schema managed via `dbDelta`; `backfill_customer_name()` updates session rows when enquiry is submitted
- Added `Admin_Menu` class — top-level "Sellora AI" admin menu (slug `sellora-ai`, position 58) with three sub-pages: Chat History, Enquiries, Settings
- Added `admin/chat-history-page.php` — paginated session list with filters (ip, name, date range)
- Added `admin/chat-session-detail-page.php` — single session message detail view
- Added `admin/enquiries-page.php` — enquiries list with name/email/date filters and pagination; resolves Known Gap #7
- Added settings fields: `company_logo`, `employee_photo`, `panel_title`, `panel_subtitle`
- Updated `Plugin::__construct()` — wires `Chat_Logger` and `Admin_Menu`; calls `Chat_Logger::maybe_create_table()` on startup
- Renamed main file from `ai-woocommerce-assistant.php` to `woocommerce-ai-chatbot-sellora.php` to match WordPress slug
- Updated `Project_context.md` to reflect all structural and behavioral changes above

### 2026-04-07 (session 1)

- Added `is_bot_request()` method to `Ajax_Controller` — checks HTTP_USER_AGENT against 17 bot/crawler signatures; empty UA is also treated as bot; check runs before nonce verification to short-circuit cheaply
- Added `wp_strip_all_tags()` to `Chat_Service::sanitize_history()` — prevents assistant HTML card markup from being sent as AI conversation context, reducing token waste on multi-turn chats
- Trimmed `short_description` to 30 words in `Catalog_Service::format_product()` — consistent with existing 50-word trim on `description`
- Changed `maybe_warn_if_woocommerce_missing` hook from `init` to `admin_init` — notice only needs to fire in admin context
- Added `id="aiwoo-chat-panel"` to the chat panel `<section>` and `aria-controls="aiwoo-chat-panel"` to the launcher button in `chat-widget.php` — fixes accessibility gap
- Removed dead `escapeHtml()` function from `chat.js` — was defined but never called
- Updated `Project_context.md` to reflect PHP requirement (`8.0`), corrected welcome_message sanitization note (already `sanitize_textarea_field`), and documented all above changes
