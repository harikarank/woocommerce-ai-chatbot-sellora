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
- send matched product context to an AI provider
- return conversational product recommendations
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
/assets/
  /css/
    style.css
  /js/
    admin.js
    chat.js
/includes/
  api-handler.php
  woocommerce-handler.php
  class-aiwoo-assistant-admin-menu.php
  class-aiwoo-assistant-ajax-controller.php
  class-aiwoo-assistant-catalog-service.php
  class-aiwoo-assistant-chat-logger.php
  class-aiwoo-assistant-chat-service.php
  class-aiwoo-assistant-claude-provider.php
  class-aiwoo-assistant-ip-blocker.php
  class-aiwoo-assistant-openai-provider.php
  class-aiwoo-assistant-plugin.php
  class-aiwoo-assistant-provider-interface.php
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

Top-level menu slug: `sellora-ai` | Icon: `dashicons-format-chat` | Position: 58

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

Currently implemented:
- `defined( 'ABSPATH' ) || exit;` in all PHP files
- bot User-Agent detection in `handle_chat()` — rejects empty UA and known crawler signatures before nonce verification
- nonce verification on AJAX handlers (`check_ajax_referer`)
- IP-based rate limiting: 15 requests per sliding minute window per hashed IP
- sanitization of:
  - settings inputs
  - chat message
  - enquiry fields
  - page context
  - conversation history (HTML stripped with `wp_strip_all_tags` before sanitize)
- API key is not exposed to frontend
- `provider` setting is forced to `openai` in `sanitize_settings` regardless of submitted value
- `maybe_warn_if_woocommerce_missing` is hooked on `admin_init` so it only runs in the admin context

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

## Known Gaps / Caveats

1. Rate limiter uses a sliding window (transient TTL resets on each request). This is more restrictive for bots doing sustained sends, but does not implement a strict fixed window. A bot doing one request every 61 seconds can send indefinitely.

2. The current "no product found" behavior is deterministic and does not use AI, because the latest requested behavior explicitly changed that flow.

3. Enquiry storage is implemented via a hidden custom post type rather than a custom database table.

4. `call_ai_model()` creates a fresh `Settings` object internally instead of receiving dependency injection when no settings object is provided via context.

5. `provider` selection supports `claude` in code, but Claude calls throw an exception. The admin UI only shows OpenAI. The `sanitize_settings` method forces `provider` to `openai` regardless of submitted value.

6. The frontend persists recent messages to `sessionStorage`, which is useful for UX but means chat history is browser-session scoped only.

7. ~~There is no separate admin UI to view stored enquiries~~ — resolved. Enquiries admin page added at `admin/enquiries-page.php` with name/email/date filters and pagination.

8. Product matching uses keyword search, not embeddings or vector retrieval.

9. Bot detection is signature-based (User-Agent strings). A sophisticated bot using a real browser User-Agent would pass this check and rely only on rate limiting and nonce expiry for containment.

## Suggested Next Improvements

- ~~add admin UI for viewing/storing/exporting enquiries~~ — done
- improve product matching with weighted ranking or taxonomy-aware search
- add provider credentials/settings for Claude before enabling that option
- implement a true fixed-window rate limiter using a timestamped transient
- consider moving `call_ai_model()` to a class-based service for stronger dependency injection
- add a honeypot field to the enquiry form for additional spam protection
- add CSV export to enquiries and chat history admin pages

## Validation Status

During implementation, the plugin files were syntax-checked with:
- `php -l` on the PHP files
- `node --check` on frontend/admin JS files

No live WordPress or WooCommerce runtime test is recorded in this repository context file.

## Change Log

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
