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
/ai-woocommerce-assistant.php
/index.php
/readme.txt
/uninstall.php
/Project_context.md
/admin/
  settings-page.php
/assets/
  /css/
    style.css
  /js/
    admin.js
    chat.js
/includes/
  api-handler.php
  woocommerce-handler.php
  class-aiwoo-assistant-ajax-controller.php
  class-aiwoo-assistant-catalog-service.php
  class-aiwoo-assistant-chat-service.php
  class-aiwoo-assistant-claude-provider.php
  class-aiwoo-assistant-openai-provider.php
  class-aiwoo-assistant-plugin.php
  class-aiwoo-assistant-provider-interface.php
  class-aiwoo-assistant-settings.php
/templates/
  chat-widget.php
```

## Main Runtime Flow

### 1. Plugin bootstrap

File: `ai-woocommerce-assistant.php`

Responsibilities:
- declares plugin headers and constants
- loads:
  - `includes/woocommerce-handler.php`
  - `includes/api-handler.php`
  - `includes/class-aiwoo-assistant-plugin.php`
- starts the plugin singleton via `\AIWooAssistant\Plugin::instance()`

### 2. Core plugin initialization

File: `includes/class-aiwoo-assistant-plugin.php`

Responsibilities:
- creates the main service objects:
  - `Settings`
  - `Catalog_Service`
  - `Chat_Service`
  - `Ajax_Controller`
- registers WordPress hooks for:
  - textdomain loading
  - WooCommerce compatibility declaration
  - hidden enquiry post type registration
  - missing-WooCommerce admin notice (hooked on `admin_init`, not `init`)
  - admin asset loading
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
- `Provider_Interface` defines `generate_response(array $payload)`
- `OpenAI_Provider` sends requests to `https://api.openai.com/v1/responses`
- `Claude_Provider` is currently a stub that throws an exception
- `includes/api-handler.php` exposes:
  - `make_ai_provider()`
  - `call_ai_model($message, $context = array())`

This is the current provider switch point and should remain the main abstraction boundary for future upgrades.

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

## Admin Settings

File: `includes/class-aiwoo-assistant-settings.php`

Implemented with the WordPress Settings API.

### Stored option name

`ai_woo_assistant_settings`

### Current settings fields

- `enabled`
- `provider`
- `openai_api_key`
- `openai_model`
- `temperature`
- `max_context_products`
- `primary_color`
- `chat_icon`
- `welcome_message`
- `system_prompt`

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
- UI strings
- theme values:
  - `primaryColor`
  - `iconUrl`
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

7. There is no separate admin UI to view stored enquiries; they are saved in the database but hidden from the admin menu.

8. Product matching uses keyword search, not embeddings or vector retrieval.

9. Bot detection is signature-based (User-Agent strings). A sophisticated bot using a real browser User-Agent would pass this check and rely only on rate limiting and nonce expiry for containment.

## Suggested Next Improvements

- add admin UI for viewing/storing/exporting enquiries
- improve product matching with weighted ranking or taxonomy-aware search
- add provider credentials/settings for Claude before enabling that option
- implement a true fixed-window rate limiter using a timestamped transient
- consider moving `call_ai_model()` to a class-based service for stronger dependency injection
- add a honeypot field to the enquiry form for additional spam protection

## Validation Status

During implementation, the plugin files were syntax-checked with:
- `php -l` on the PHP files
- `node --check` on frontend/admin JS files

No live WordPress or WooCommerce runtime test is recorded in this repository context file.

## Change Log

### 2026-04-07

- Added `is_bot_request()` method to `Ajax_Controller` — checks HTTP_USER_AGENT against 17 bot/crawler signatures; empty UA is also treated as bot; check runs before nonce verification to short-circuit cheaply
- Added `wp_strip_all_tags()` to `Chat_Service::sanitize_history()` — prevents assistant HTML card markup from being sent as AI conversation context, reducing token waste on multi-turn chats
- Trimmed `short_description` to 30 words in `Catalog_Service::format_product()` — consistent with existing 50-word trim on `description`
- Changed `maybe_warn_if_woocommerce_missing` hook from `init` to `admin_init` — notice only needs to fire in admin context
- Added `id="aiwoo-chat-panel"` to the chat panel `<section>` and `aria-controls="aiwoo-chat-panel"` to the launcher button in `chat-widget.php` — fixes accessibility gap
- Removed dead `escapeHtml()` function from `chat.js` — was defined but never called
- Updated `Project_context.md` to reflect PHP requirement (`8.0`), corrected welcome_message sanitization note (already `sanitize_textarea_field`), and documented all above changes
