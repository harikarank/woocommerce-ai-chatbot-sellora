# Sellora AI — WooCommerce AI Chatbot & Shopping Assistant

A production-grade WordPress plugin that adds an AI-powered chat widget to any WooCommerce store. Customers type naturally and receive instant, grounded product recommendations. Supports **OpenAI**, **Claude (Anthropic)**, and **Gemini (Google)** — switchable at any time from the admin settings.

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

Switch providers from **Sellora AI → Settings → General**. Only the fields for the selected provider are shown — no clutter.

### Chat Widget

- Floating launcher button fixed to the bottom-right corner
- Animated, responsive panel — full-screen on mobile, floating on desktop
- Product recommendation cards shown inline with AI response
- Enquiry form rendered inside the chat when no products match
- Live character counter with configurable message length limit (default: 200 chars)
- Chat state and recent message history persisted across page loads (sessionStorage)
- Configurable welcome message, panel title, subtitle, company logo, and assistant avatar

### WooCommerce Integration

- Searches your real product catalog using keyword matching against WooCommerce products
- Sends live product data (name, price, stock status, description, categories) to the AI — no hallucinated products
- Detects the current product page and includes that product first in AI context
- Falls back to a contact form when no products match the query

### Admin Settings (Tabbed)

| Tab | Contents |
|---|---|
| **General** | Provider, API keys, model, temperature, catalog size, message length limit |
| **Widget** | Panel title/subtitle, company logo, assistant avatar, launcher icon, welcome message |
| **Appearance** | 17 colour pickers (accent, header, bubbles, input, send button, text) |
| **AI & Prompt** | Additional system prompt instructions appended to the built-in base prompt |

### Security

| Layer | Detail |
|---|---|
| IP Blocklist | Exact IPv4/IPv6 and CIDR ranges — blocks widget render + all AJAX requests server-side |
| Auto IP Block | Message exceeding the length limit → sender IP auto-added to blocklist |
| Bot Detection | 17 User-Agent signatures checked before nonce verification |
| Rate Limiting | 15 requests per sliding minute per IP |
| Nonce | `check_ajax_referer` on every AJAX handler |
| Sanitisation | All inputs sanitised; API keys never output to the frontend |

### Chat Logging & Enquiries

- Custom DB table `{prefix}aiwoo_chat_logs` — session grouping, IP, customer name, timestamps
- Customer name backfilled in chat logs when they submit an enquiry
- Enquiries saved as private WordPress posts (`aiwoo_enquiry`) and emailed to the site admin
- Both are viewable and filterable in the WordPress admin

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.4 |
| WooCommerce | 7.8 |
| PHP | 8.0 |
| AI Provider | API key for OpenAI, Anthropic, or Google |

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

On the **Appearance** tab, 17 colour pickers let you match the widget to your brand:

| Section | Fields |
|---|---|
| Accent | Accent colour, Accent hover colour |
| Panel & Layout | Widget background, Messages area background, Border colour, Body text, Secondary text |
| Header | Header background, Header text & icons |
| Messages | User bubble background/text, Agent bubble background/text |
| Input & Send | Input background/text, Send button background/text/hover |

**Leave any field blank** to use the built-in default. You only need to set the fields you want to override.

---

## Admin Pages

The plugin adds a top-level **Sellora AI** menu to the WordPress admin with four sub-pages:

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

### Settings (`Sellora AI → Settings`)

Four-tab settings panel — see [Configuration](#configuration) above.

---

## Security

### How the chat AJAX endpoint is protected

Every incoming chat request passes through the following checks in order. A request is rejected the moment it fails any check — later checks are never reached:

```
1. Plugin enabled?          → 403 if disabled
2. IP on blocklist?         → 403 if blocked (exact or CIDR match)
3. Bot User-Agent?          → 403 if matched (17 known signatures + empty UA)
4. Valid nonce?             → 403 if invalid (check_ajax_referer)
5. Rate limit OK?           → 429 if > 15 requests/min for this IP
6. Payload size OK?         → 413 if history > 8 000 chars or context > 4 000 chars
7. Message length OK?       → 413 + auto IP block if message > max_message_length
8. Sanitise all inputs
9. Call AI provider
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
├── uninstall.php                        # Cleanup on plugin deletion
├── index.php                            # Directory index guard
│
├── includes/
│   ├── class-aiwoo-assistant-plugin.php           # Singleton bootstrap — wires all services
│   ├── class-aiwoo-assistant-settings.php         # Settings API — option: ai_woo_assistant_settings
│   ├── class-aiwoo-assistant-ajax-controller.php  # AJAX handlers for chat + enquiry
│   ├── class-aiwoo-assistant-chat-service.php     # Chat orchestration — prompt building, product lookup
│   ├── class-aiwoo-assistant-catalog-service.php  # WooCommerce product search
│   ├── class-aiwoo-assistant-chat-logger.php      # DB table: {prefix}aiwoo_chat_logs
│   ├── class-aiwoo-assistant-admin-menu.php       # Admin menu + page renderers
│   ├── class-aiwoo-assistant-ip-blocker.php       # IP blocklist — exact + CIDR (IPv4/IPv6)
│   ├── class-aiwoo-assistant-provider-interface.php  # Provider contract
│   ├── class-aiwoo-assistant-openai-provider.php     # OpenAI — /v1/responses
│   ├── class-aiwoo-assistant-claude-provider.php     # Claude — /v1/messages
│   ├── class-aiwoo-assistant-gemini-provider.php     # Gemini — /v1beta/models/{model}:generateContent
│   ├── api-handler.php                            # make_ai_provider() + call_ai_model() factory
│   └── woocommerce-handler.php                    # WooCommerce compatibility helpers
│
├── admin/
│   ├── settings-page.php           # Tabbed settings UI (General / Widget / Appearance / AI & Prompt)
│   ├── chat-history-page.php       # Session list with filters
│   ├── chat-session-detail-page.php  # Single session conversation thread
│   ├── enquiries-page.php          # Enquiry list with filters
│   └── ip-blocklist-page.php       # IP blocklist add/delete UI
│
├── templates/
│   └── chat-widget.php             # Frontend widget HTML (launcher + chat panel)
│
└── assets/
    ├── css/
    │   └── style.css               # Widget styles — all colours driven by CSS custom properties
    └── js/
        ├── chat.js                 # Frontend widget logic — AJAX, character counter, state
        └── admin.js                # Admin — colour pickers, media uploader, tab switching, provider rows
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

- Product `short_description` trimmed to 30 words; `description` to 50 words before being included in context
- Conversation history HTML-stripped before sending (prevents previous product card HTML from counting against the token budget)
- History capped at 8 entries stored, 6 entries sent to AI; each entry truncated to 1 000 characters
- WooCommerce product query uses `fields => ids`, `no_found_rows => true`, and disabled cache updates
- Message length hard-capped at a configurable limit (default 200 chars); overages auto-block the IP

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

- OpenAI, Claude (Anthropic), and Gemini (Google) provider integrations
- Floating chat widget with product recommendation cards and enquiry form fallback
- Tabbed admin settings (General, Widget, Appearance, AI & Prompt)
- 17-field colour customisation system driven by CSS custom properties
- Configurable message length limit with live character counter and auto IP block
- IP blocklist admin page — exact IPs and CIDR ranges (IPv4 + IPv6)
- Chat history admin page — session list, filters, detail view
- Enquiries admin page — filterable list with email notification
- Custom DB table for chat logs with session grouping
- Bot detection, nonce verification, rate limiting, input sanitisation
