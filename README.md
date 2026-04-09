# Sellora AI — WooCommerce AI Chatbot & Shopping Assistant

A production-grade WordPress plugin that adds an AI-powered chat widget to any WooCommerce store. Customers type naturally and receive instant, grounded product recommendations. Supports **OpenAI**, **Claude (Anthropic)**, and **Gemini (Google)** — switchable at any time from the admin settings. Includes a rule-based Quick Reply engine to bypass the AI for common queries, MCP tool-calling mode for on-demand product lookups, personalisation, upsell/cross-sell intelligence, and a full admin dashboard (chat history, enquiries, IP blocklist, quick replies, top requests analytics, AI error log, plugin guide).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Step 1 — Choose your AI provider](#step-1--choose-your-ai-provider)
  - [Step 2 — Get your API key](#step-2--get-your-api-key)
  - [Step 3 — Configure the widget](#step-3--configure-the-widget)
  - [Step 4 — Customise colours](#step-4--customise-colours)
  - [Step 5 — AI Intelligence (optional)](#step-5--ai-intelligence-optional)
- [Admin Pages](#admin-pages)
- [Security](#security)
- [Project Structure](#project-structure)
- [Developer Notes](#developer-notes)
- [Changelog](#changelog)

---

## Features

### Multi-Provider AI

| Provider | Models |
|---|---|
| **OpenAI** | gpt-5.4-mini *(default)*, gpt-5.4, gpt-4.1-mini |
| **Claude (Anthropic)** | claude-sonnet-4-6 *(default)*, claude-opus-4-6, claude-haiku-4-5-20251001 |
| **Gemini (Google)** | gemini-2.5-flash *(default)*, gemini-2.5-pro, gemini-2.5-flash-lite, gemini-2.0-flash-lite, gemini-1.5-pro, gemini-1.5-flash |

Switch providers from **Sellora AI → Settings → General**. Only the fields for the selected provider are shown — no clutter. All three providers enforce a `max_tokens` cap of 1024 per request to prevent runaway cost.

### Two Operating Modes

| Mode | Behaviour |
|---|---|
| **Legacy (default)** | Plugin searches WooCommerce by keyword, injects matching products into the prompt, AI responds |
| **MCP Tool Calling** | AI decides which products to fetch via `get_products`, `get_product_details`, `get_related_products`, and `get_user_context` tool calls on demand — no product data in the initial prompt |

MCP mode reduces token usage and improves accuracy. Enable it under **Settings → AI Intelligence**. Works with all three providers (OpenAI function calling, Claude tool_use, Gemini functionCall).

### Chat Widget

- Floating launcher button fixed to the bottom-right corner
- Animated, responsive panel — full-screen on mobile, floating on desktop
- Product recommendation cards shown inline with AI response (configurable: price, stock, image, description, view link)
- Square product thumbnails (1:1 aspect ratio)
- Enquiry form rendered inside the chat when no products match, with honeypot anti-spam
- Live character counter with configurable message length limit (default: 200 chars)
- Chat state and recent message history persisted across page loads (sessionStorage)
- Clear chat button in the panel header
- Configurable welcome message, panel title, subtitle, company logo, and assistant avatar
- Configurable corner radius (0–24 px) applied to panel, message bubbles, and enquiry form

### WooCommerce Integration

- Searches your real product catalog using keyword matching against WooCommerce products
- Sends live product data (name, price, stock, description, attributes) to the AI — no hallucinated products
- Detects the current product page; includes that product in AI context only when the user message references it (keyword overlap check)
- Falls back to product cards from catalog search when the AI provider is unavailable
- Falls back to a contact form when no products match the query
- HPOS (High-Performance Order Storage) compatible

### Personalisation & Upsell (MCP mode)

- **Personalisation:** Tracks viewed products and chat-derived search history in browser sessionStorage. The AI can query a `get_user_context` tool to tailor recommendations based on recent behaviour. No PII leaves the browser unless the AI explicitly calls the tool.
- **Upsell / Cross-sell:** Exposes a `get_related_products` tool that returns WooCommerce-configured upsell and cross-sell IDs for a given product, letting the AI suggest "You may also like…" items.

### Quick Replies (Rule-Based Bypass)

- Define keyword rules that return instant responses without calling the AI at all — great for FAQs like store hours, return policy, or shipping info
- 69 default rules seeded on activation (greetings, order tracking, returns, delivery, pricing, etc.) — all editable
- Match types: `contains` (keyword anywhere) or `exact` (full-message match)
- Priority-based ordering — highest priority wins when multiple rules match
- One-hour transient cache, flushed automatically on any rule change
- Admin page: **Sellora AI → Quick Replies**

### Top Requests Analytics

- Aggregates chat logs by normalised message to surface the most frequent customer queries
- Type badges distinguish "Quick Reply" vs "AI Response" at a glance
- Inline "Save as Quick Reply" button turns high-frequency AI responses into rule-based bypasses — directly reduces AI usage
- Filters: search, type (all / quick reply / AI), date (7 / 30 days / all time)
- CSV export
- Admin page: **Sellora AI → Top Requests**

### Admin Settings (Tabbed)

| Tab | Contents |
|---|---|
| **General** | Provider, API keys (masked), model, temperature, catalog size, message length limit |
| **Widget** | Panel title/subtitle, company logo, assistant avatar, launcher icon, welcome message, enquiry form intro, product card field toggles, no-match fallback text |
| **Appearance** | 24 colour pickers, corner radius (panel/bubbles/form) |
| **AI & Prompt** | Additional system prompt instructions appended to the built-in base prompt |
| **AI Intelligence** | MCP tool calling, max products per tool call, personalisation, upsell/cross-sell |

### API Key Security

- API keys are never rendered as HTML values in the settings form. Instead, a masked placeholder (first 4 + last 4 characters) is shown
- Saving with a blank API key field **preserves** the existing key — it is never accidentally wiped
- Enter a new key to replace the current one
- Keys are stored server-side only; the frontend never receives them

### Security

| Layer | Detail |
|---|---|
| IP Blocklist | Exact IPv4/IPv6 and CIDR ranges — blocks widget render + all AJAX requests server-side |
| Auto IP Block | Message exceeding the length limit → sender IP auto-added to blocklist |
| Bot Detection | 17 User-Agent signatures checked before nonce verification |
| Rate Limiting | 15 requests per fixed 60-second window per IP (chat and enquiry) |
| Nonce | `check_ajax_referer` on every AJAX handler |
| Sanitisation | All inputs sanitised; API keys never output to the frontend |
| Honeypot | Hidden field on enquiry form silently rejects bot submissions |
| Token Cap | Every AI provider call capped at `max_tokens = 1024` to cap cost |
| Error Masking | Provider exceptions never forwarded to the browser — logged server-side only |

### Chat Logging, Enquiries & Error Log

- Custom DB table `{prefix}aiwoo_chat_logs` — session grouping, IP, customer name, timestamps
- Customer name backfilled in chat logs when they submit an enquiry
- Enquiries saved as private WordPress posts (`aiwoo_enquiry`) and emailed to the site admin
- Custom DB table `{prefix}aiwoo_ai_error_logs` — records AI provider failures (context: `ajax` hard failure, `mcp` fallback, `legacy` fallback). Admin-only; users never see these details.
- All are viewable and filterable in the WordPress admin

---

## Requirements

| Requirement | Minimum | Tested up to |
|---|---|---|
| WordPress | 6.4 | current |
| WooCommerce | 7.8 | 9.0 |
| PHP | 8.0 | 8.4 |
| AI Provider | API key for OpenAI, Anthropic, or Google | — |

> **WooCommerce is required** for product-aware responses. The widget loads without it but will not surface product recommendations.

---

## Installation

### Option A — Upload via WordPress Admin

1. Download the plugin as a `.zip` file.
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip, click **Install Now**, then **Activate Plugin**.

### Option B — Manual (FTP / SFTP)

1. Extract the zip file on your local machine.
2. Upload the `woocommerce-ai-chatbot-sellora/` folder to `/wp-content/plugins/` on your server.
3. In WordPress admin go to **Plugins → Installed Plugins** and activate **Sellora AI**.

### Option C — Clone from Git (Development)

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/your-org/woocommerce-ai-chatbot-sellora.git
```

Activate via the WordPress admin. No build step required — vanilla PHP and JavaScript.

---

## Configuration

### Step 1 — Choose your AI provider

Go to **Sellora AI → Settings → General** in your WordPress admin sidebar.

From the **AI provider** dropdown, select one of:
- `OpenAI`
- `Claude (Anthropic)`
- `Gemini (Google)`

The credential fields update instantly to show only the inputs relevant to your selection.

---

### Step 2 — Get your API key

**OpenAI**

1. Visit [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Click **Create new secret key** — give it a name (e.g. `Sellora AI`)
3. Copy the key immediately (it is only shown once)
4. Paste it into **OpenAI API key** in the settings

**Claude (Anthropic)**

1. Visit [console.anthropic.com/account/keys](https://console.anthropic.com/account/keys)
2. Click **Create Key** — name it (e.g. `Sellora AI`)
3. Copy the key
4. Paste it into **Anthropic API key** in the settings

**Gemini (Google)**

1. Visit [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)
2. Click **Create API key**
3. Copy the key
4. Paste it into **Google AI Studio API key** in the settings

---

### Step 3 — Configure the widget

On the **General** tab:
- **Max message length** — how many characters customers can type per message (default: 200). Messages exceeding this limit are rejected and the sender's IP is auto-blocked.
- **Response temperature** — controls AI creativity (0 = precise, 1 = creative). Recommended: 0.3–0.5 for product recommendations.
- **Catalog products in context** — how many products are sent to the AI per request (1–10, default: 4).

On the **Widget** tab:
- **Panel header title** — leave blank to use your site name
- **Panel header subtitle** — shown below the title in the chat header
- **Panel header logo** — round logo in the chat header (upload via media library)
- **Assistant avatar photo** — round photo shown next to each AI reply
- **Chat launcher icon** — replaces the default chat icon on the floating button
- **Welcome message** — the first message shown when the chat opens

Enable the widget with the **Enable widget** checkbox, then click **Save Changes**.

---

### Step 4 — Customise colours

On the **Appearance** tab, 24 colour pickers let you match the widget to your brand:

| Section | Fields |
|---|---|
| Accent | Accent colour, Accent hover colour |
| Panel & Layout | Widget background, Messages area background, Border colour, Body text, Secondary text |
| Shape | Corner radius (0–24 px) applied to panel, bubbles, and enquiry form |
| Panel Borders | Panel border colour, Header bottom border colour |
| Header | Header background, Header text & icons |
| Messages | User bubble background/text, Agent bubble background/text |
| Input & Send | Form background, Form border, Input background/text, Send button background/text/hover |
| Typing & Counter | Typing indicator background/text, Character counter background/text |

**Leave any field blank** to use the built-in default. You only need to set the fields you want to override.

### Step 5 — AI Intelligence (optional)

On the **AI Intelligence** tab:

- **Enable MCP tool calling** — let the AI fetch products via tool calls instead of receiving all catalog data upfront. Reduces token cost and improves accuracy.
- **Max products per tool call** — 1–10 (default 5). Limits what a single `get_products` call returns.
- **Enable personalisation** — expose `get_user_context` tool (viewed products, chat-derived searches, cart). Requires MCP mode.
- **Enable upsell / cross-sell** — expose `get_related_products` tool returning WooCommerce-configured upsells and cross-sells. Requires MCP mode.

---

## Admin Pages

The plugin adds a top-level **Sellora AI** menu to the WordPress admin with eight sub-pages:

### Chat History (`Sellora AI → Chat History`)

Displays all chat sessions grouped by session ID, ordered newest first.

- **Filters:** IP address, customer name (partial match), date from, date to
- **Columns:** Session ID, IP address, customer name, first message, message count, start and last activity time
- **Detail view:** Click any session to see the full conversation thread

### Enquiries (`Sellora AI → Enquiries`)

Displays all enquiry form submissions.

- **Filters:** Customer name, email address, date
- Shows name, email, phone, message, and submission date

### IP Blocklist (`Sellora AI → IP Blocklist`)

Manage the list of blocked IP addresses and ranges.

**To add an entry:**
- Enter an exact IPv4 address: `203.0.113.45`
- Enter an exact IPv6 address: `2001:db8::1`
- Enter an IPv4 CIDR range: `203.0.113.0/24`
- Enter an IPv6 CIDR range: `2001:db8::/32`

**To delete:** Click the **Delete** button next to any entry and confirm.

> The blocklist is enforced entirely server-side. Blocked visitors receive no widget HTML, no JavaScript, and no API responses. There is nothing to bypass from the browser.

### Quick Replies (`Sellora AI → Quick Replies`)

Manage rule-based keyword matches that bypass the AI entirely.

- **List view:** all rules sorted by priority, with title, keywords, match type, response preview, status
- **Add / Edit form:** title, comma-separated keywords, match type (contains/exact), response text, priority, active toggle
- **Delete** with confirmation
- Rule changes invalidate the cache instantly

### Top Requests (`Sellora AI → Top Requests`)

Aggregated analytics view of the most frequent customer queries.

- **Filters:** search, type (all / quick reply / AI response), date range (7 / 30 days / all time)
- **Inline action:** turn any AI-answered query into a Quick Reply rule with one click
- **CSV export** of the filtered result set
- Popular queries (>= 100 hits) are flagged with a 🔥 badge

### AI Error Log (`Sellora AI → AI Error Log`)

Admin-only view of AI provider failures. Three error contexts:

- **No Response** (red) — hard failure; user saw a generic error
- **MCP Fallback** (amber) — MCP path failed; user saw fallback product cards
- **Legacy Fallback** (amber) — legacy path failed; user saw fallback product cards

Shows timestamp, context, IP, user message, and truncated error detail. Customer-facing users never see any of this.

### Plugin Guide (`Sellora AI → Plugin Guide`)

In-dashboard documentation covering every feature: providers, widget settings, product cards, MCP mode, quick replies, IP blocklist, chat history, enquiries, and the system prompt.

### Settings (`Sellora AI → Settings`)

Five-tab settings panel — see [Configuration](#configuration) above.

---

## Security

### How the chat AJAX endpoint is protected

Every incoming chat request passes through the following checks in order. A request is rejected the moment it fails any check — later checks are never reached:

```
1. Plugin enabled?          → 403 if disabled
2. IP on blocklist?         → 403 if blocked (exact or CIDR match)
3. Bot User-Agent?          → 403 if matched (17 known signatures + empty UA)
4. Valid nonce?             → 403 if invalid (check_ajax_referer)
5. Rate limit OK?           → 429 if > 15 requests in the current 60-second window
6. Payload size OK?         → 413 if history > 8 000 chars or context > 6 000 chars
7. Message length OK?       → 413 + auto IP block if message > max_message_length
8. Sanitise all inputs
9. Quick Reply check (bypass AI if matched)
10. Call AI provider (with max_tokens = 1024 cap)
```

### IP blocklist details

- Stored in WordPress option `aiwoo_blocked_ips` (autoload disabled)
- Maximum 500 entries
- IPv4 CIDR matching uses `ip2long` bitwise mask
- IPv6 CIDR matching uses `inet_pton` binary byte comparison
- IP source is always `$_SERVER['REMOTE_ADDR']` — `X-Forwarded-For` is never trusted (to prevent header spoofing)

> **Behind a reverse proxy (Cloudflare, Nginx, load balancer)?** Configure your proxy to write the real client IP into `REMOTE_ADDR` at the server level before PHP runs. Do not modify the plugin to read proxy headers — doing so opens IP spoofing.

---

## Project Structure

```
woocommerce-ai-chatbot-sellora/
│
├── woocommerce-ai-chatbot-sellora.php   # Plugin entry — headers, constants, requires, activation hook
├── uninstall.php                        # Cleanup on plugin deletion (options + 3 custom tables + enquiry CPT)
├── index.php                            # Directory index guard
│
├── includes/
│   ├── class-aiwoo-assistant-plugin.php              # Singleton bootstrap — lazy service getters
│   ├── class-aiwoo-assistant-settings.php            # Settings API — option: ai_woo_assistant_settings
│   ├── class-aiwoo-assistant-ajax-controller.php     # AJAX handlers for chat + enquiry
│   ├── class-aiwoo-assistant-chat-service.php        # Chat orchestration — legacy + MCP paths
│   ├── class-aiwoo-assistant-catalog-service.php     # WooCommerce product search + formatting
│   ├── class-aiwoo-assistant-mcp-tools.php           # MCP tool registry + executor (4 tools)
│   ├── class-aiwoo-assistant-chat-logger.php         # DB table: {prefix}aiwoo_chat_logs
│   ├── class-aiwoo-assistant-ai-error-logger.php     # DB table: {prefix}aiwoo_ai_error_logs
│   ├── class-aiwoo-assistant-quick-reply-service.php # DB table: {prefix}aiwoo_quick_replies + 69 seeds
│   ├── class-aiwoo-assistant-admin-menu.php          # Admin menu + page renderers
│   ├── class-aiwoo-assistant-ip-blocker.php          # IP blocklist — exact + CIDR (IPv4/IPv6)
│   ├── class-aiwoo-assistant-provider-interface.php  # Provider contract (generate_response + generate_with_tools)
│   ├── class-aiwoo-assistant-openai-provider.php     # OpenAI — /v1/responses + /v1/chat/completions
│   ├── class-aiwoo-assistant-claude-provider.php     # Claude — /v1/messages (Messages API)
│   ├── class-aiwoo-assistant-gemini-provider.php     # Gemini — /v1beta/models/{model}:generateContent
│   ├── api-handler.php                               # make_ai_provider() + call_ai_model() factory
│   └── woocommerce-handler.php                       # WooCommerce compatibility helpers
│
├── admin/
│   ├── settings-page.php              # Tabbed settings UI (5 tabs)
│   ├── chat-history-page.php          # Session list with filters
│   ├── chat-session-detail-page.php   # Single session conversation thread
│   ├── enquiries-page.php             # Enquiry list with filters
│   ├── ip-blocklist-page.php          # IP blocklist add/delete UI
│   ├── quick-replies-page.php         # Quick reply rule list + add/edit/delete
│   ├── top-requests-page.php          # Top queries analytics + CSV export + inline save-as-QR
│   ├── ai-errors-page.php             # AI provider failure log
│   └── info-page.php                  # Plugin Guide / in-dashboard documentation
│
├── templates/
│   └── chat-widget.php                # Frontend widget HTML (launcher + chat panel)
│
└── assets/
    ├── css/
    │   └── style.css                  # Widget styles — all colours driven by CSS custom properties
    ├── js/
    │   ├── chat.js                    # Frontend widget — AJAX, counter, state, personalisation tracking
    │   └── admin.js                   # Admin — colour pickers, media uploader, tab switching, provider rows
    └── img/
        ├── favicon.svg                # Default launcher icon
        └── logo.svg                   # Default panel header logo
```

---

## Developer Notes

### Adding a new AI provider

1. Create `includes/class-aiwoo-assistant-{name}-provider.php` implementing `Provider_Interface`:

```php
namespace AIWooAssistant;

final class My_Provider implements Provider_Interface {
    private $settings;

    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    public function generate_response( array $payload ) {
        // $payload['instructions'] = system prompt
        // $payload['input']        = user message + product context
        // return string
    }
}
```

2. Add `require_once` for the new class in `includes/api-handler.php`
3. Add a `case 'myprovider':` to `make_ai_provider()` in the same file
4. Add the provider to the `$allowed_providers` array in `Settings::sanitize_settings()`
5. Add an `<option>` for the provider in `Settings::render_field()` case `'provider'`
6. Add `api_key` and `model` settings following the pattern of the existing providers

### CSS colour system

All widget colours are CSS custom properties defined on `.aiwoo-widget`:

```css
.aiwoo-widget {
    --aiwoo-primary:          #9a162d;
    --aiwoo-primary-dark:     #7d1125;
    --aiwoo-header-bg:        var(--aiwoo-primary);
    --aiwoo-user-bubble-bg:   var(--aiwoo-primary);
    /* ... 18 total variables */
}
```

Per-site overrides are injected by PHP via `wp_add_inline_style` immediately after the stylesheet:

```css
.aiwoo-widget {
    --aiwoo-primary: #1a56db;  /* only set variables are output */
}
```

No JavaScript is involved in colour application.

### Token efficiency

The following measures reduce AI API cost per request:

- **Trimmed system prompts** — anti-hallucination rules merged into a single line; store metadata compressed
- **Slim product context** — `format_product()` drops `sku`, `tags`, `categories`; `sale_price`/`regular_price` only included when on sale; whitespace collapsed; HTML entities decoded
- Product `short_description` trimmed to 30 words; `description` to 50 words before being included in context
- Products fed to prompt in slim JSON: `{name, price, url, desc?, stock?}`
- **Smart history** — capped at 6 entries stored, 4 sent to AI; each entry truncated to 500 chars; assistant replies reduced to the first sentence only (strips product card text)
- **Context pruning** — `pageUrl` dropped from AI context entirely; current product context sent only when the user message contains a keyword from the product name
- Conversation history HTML-stripped before sending (prevents previous product card HTML from counting against the token budget)
- **MCP mode** — AI fetches product data via tool calls on demand instead of receiving all product data upfront; transient cache 120s; per-tool call cap 3/request; max 5 API rounds
- **MCP tool results** — omit `stock_status` when "instock" (assumed default); drop `sku`
- **Provider-level caps** — every API call enforces `max_tokens` / `max_output_tokens = 1024`
- **Quick Reply bypass** — keyword-matched queries skip the AI entirely (1 hour cache)
- WooCommerce product query uses `fields => ids`, `no_found_rows => true`, and disabled cache updates
- Message length hard-capped at a configurable limit (default 200 chars); overages auto-block the IP

### Runtime efficiency (lazy loading)

`Plugin::__construct()` no longer instantiates the full service graph on every request. Only `Settings` and `IP_Blocker` are eager. All other services are built lazily via private getters the first time they are needed:

- **Frontend page load (widget enabled):** 3 objects (Settings, IP_Blocker, Catalog_Service for product context)
- **Frontend page load (widget disabled / blocked IP):** 2 objects (Settings, IP_Blocker)
- **AJAX chat request:** full service chain instantiated lazily inside the handler
- **Admin page load:** Settings + IP_Blocker + Admin_Menu and its dependencies

Admin bar rendering lives directly on the `Plugin` class so logged-in users see the frontend admin bar without forcing `Admin_Menu` and its dependencies to instantiate.

### Coding conventions

- Namespace: `AIWooAssistant`
- Class files: `class-aiwoo-assistant-{name}.php`
- Text domain: `ai-woocommerce-assistant`
- Every PHP file starts with `defined( 'ABSPATH' ) || exit;`
- WordPress coding standards (spaces not tabs, snake_case, Yoda conditions)
- No Composer, no npm, no build step — pure PHP and vanilla JavaScript

---

## Changelog

### 1.0.0

**AI providers & modes**
- OpenAI, Claude (Anthropic), and Gemini (Google) provider integrations
- Two modes: legacy prompt-based path and MCP tool-calling path
- Tool calls supported across all three providers (OpenAI function calling, Claude tool_use, Gemini functionCall)
- `max_tokens = 1024` cap on every provider call
- API keys masked in the settings form; blank save preserves existing key

**Chat widget**
- Floating chat widget with product recommendation cards and enquiry form fallback
- Product card field toggles (price, stock, image, description, view link)
- Square (1:1) product thumbnails
- Configurable corner radius (0–24 px)
- Live character counter with configurable message length limit + auto IP block
- Persistent chat state (sessionStorage), clear chat button
- Honeypot anti-spam on enquiry form
- AI failure fallback — user sees product cards from catalog search instead of a dead-end error

**Admin features**
- Tabbed settings (General, Widget, Appearance, AI & Prompt, AI Intelligence) — 24 colour pickers
- Chat history admin page — session list, filters (IP / name / date), detail view
- Enquiries admin page — filterable list with email notification
- IP blocklist admin page — exact IPs and CIDR ranges (IPv4 + IPv6)
- Quick Replies admin page — rule-based keyword matches that bypass the AI (69 default rules seeded)
- Top Requests analytics page — most frequent queries with type badges, inline save-as-Quick-Reply, CSV export
- AI Error Log admin page — server-side logs of provider failures (never exposed to users)
- Plugin Guide in-dashboard documentation
- Admin bar shortcut with Chat History, AI Error Log, and Settings sub-items

**Personalisation & upsell (MCP mode)**
- Viewed products and search history tracked in sessionStorage; exposed to the AI via `get_user_context` tool
- Upsell and cross-sell products exposed via `get_related_products` tool

**Security & reliability**
- Fixed-window rate limiting (15 req / 60 s per IP) on chat and enquiry
- Bot detection (17 User-Agent signatures), nonce verification on every AJAX handler
- Input sanitisation throughout; provider exceptions never forwarded to the browser
- Custom DB tables: `{prefix}aiwoo_chat_logs`, `{prefix}aiwoo_quick_replies`, `{prefix}aiwoo_ai_error_logs`
- HPOS (WooCommerce High-Performance Order Storage) compatible
- Full cleanup in `uninstall.php` (options, 3 tables, enquiry CPT)

**Performance**
- Lazy service loading — frontend page loads instantiate only 2–3 objects instead of 10
- Quick Reply transient cache (1 hour)
- MCP tool result transient cache (120 s)
- Trimmed prompts, slim product data, first-sentence history reduction — ~35–50% token savings on typical multi-turn chats
