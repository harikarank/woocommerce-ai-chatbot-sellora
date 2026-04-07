# Sellora AI — Project Memory

**Name:** WooCommerce AI Chatbot & Shopping Assistant - Sellora AI
**Stack:** PHP 8.0+, WordPress 6.4+, WooCommerce 7.8+, vanilla JS, no build step
**Purpose:** Floating storefront chat widget that keyword-searches WC products, sends context to OpenAI, returns conversational recommendations; falls back to enquiry form on no match

## Key Files

- `woocommerce-ai-chatbot-sellora.php` — entry point, constants, requires
- `includes/class-aiwoo-assistant-plugin.php` — singleton bootstrap, wires all services
- `includes/class-aiwoo-assistant-ajax-controller.php` — AJAX actions `ai_woo_assistant_chat` / `ai_woo_assistant_enquiry`
- `includes/class-aiwoo-assistant-chat-service.php` — chat decision logic, prompt building
- `includes/class-aiwoo-assistant-catalog-service.php` — keyword product search via `WP_Query`
- `includes/class-aiwoo-assistant-settings.php` — Settings API, option `ai_woo_assistant_settings`
- `includes/api-handler.php` — `make_ai_provider()`, `call_ai_model()` (AI abstraction entry)
- `includes/class-aiwoo-assistant-openai-provider.php` — only working AI provider
- `includes/class-aiwoo-assistant-claude-provider.php` — stub, throws exception, do not enable
- `includes/class-aiwoo-assistant-chat-logger.php` — custom DB table for chat history
- `includes/class-aiwoo-assistant-ip-blocker.php` — IP blocklist (exact + CIDR, IPv4 + IPv6); handles admin-post add/delete
- `includes/class-aiwoo-assistant-admin-menu.php` — admin menu wiring
- `templates/chat-widget.php` — frontend widget HTML
- `assets/js/chat.js` — frontend AJAX + widget behavior
- `assets/js/admin.js` — color picker + media uploader init
- `assets/css/style.css` — widget styles
- `admin/settings-page.php` — settings UI
- `admin/enquiries-page.php` — enquiries admin view
- `admin/chat-history-page.php` — chat history list
- `admin/chat-session-detail-page.php` — single session view
- `admin/ip-blocklist-page.php` — IP blocklist admin UI (add/delete, table)

## Constants

`AI_WOO_ASSISTANT_VERSION`, `AI_WOO_ASSISTANT_FILE`, `AI_WOO_ASSISTANT_PATH`, `AI_WOO_ASSISTANT_URL`

## Conventions

- Namespace: `AIWooAssistant`
- Class files: `class-aiwoo-assistant-{name}.php`
- Text domain: `ai-woocommerce-assistant`
- Every PHP file starts with `defined( 'ABSPATH' ) || exit;`
- WordPress coding standards (spaces not tabs, snake_case, Yoda conditions)
- No Composer, no npm, no build pipeline

## AJAX Security Order

IP block → bot UA → nonce (`ai_woo_assistant_nonce`) → rate limit (15/min) → **message length check + auto-block** → sanitize

## Message Length Limit

- Setting: `max_message_length` (10–1000, default 200)
- Frontend: `maxlength` attribute on textarea + live character counter (`.aiwoo-char-counter`), turns amber at 85% and red at 100%
- Backend: if message exceeds limit, sender IP is auto-added to blocklist via `IP_Blocker::add()`, returns generic 403
- Counter injected by JS between loading indicator and form — no template changes

## Color Customization

- 17 color settings (all `color_*` keys) stored in `ai_woo_assistant_settings` option
- PHP `Plugin::build_color_css()` outputs `.aiwoo-widget{--var:value}` via `wp_add_inline_style` — overrides CSS defaults
- Empty value = use CSS default (no override output)
- All colors map 1:1 to CSS custom properties on `.aiwoo-widget`

## Admin Settings Tabs

- 4 tabs: General, Widget, Appearance, AI & Prompt
- Tabs implemented with WP `.nav-tab` classes + plain JS in `admin.js`
- Active tab persisted to `localStorage` key `aiwoo_settings_active_tab`
- Template passes `$settings` object; renders fields via `$settings->render_field()`

## IP Blocklist

- Option: `aiwoo_blocked_ips` (autoload=false), array of strings, max 500 entries
- Supports: exact IPv4, exact IPv6, IPv4 CIDR (e.g. `10.0.0.0/8`), IPv6 CIDR (e.g. `2001:db8::/32`)
- Enforcement: server-side only — widget HTML + assets not rendered for blocked IPs; AJAX handlers return 403
- Admin page slug: `sellora-ai-ip-blocklist`; form actions: `aiwoo_add_blocked_ip`, `aiwoo_delete_blocked_ip` (admin-post.php)
- IP source: `REMOTE_ADDR` only (no `X-Forwarded-For`) — safe against header spoofing
- Cleaned up in `uninstall.php`

## AI Provider Status

All three providers are fully implemented. Selection is saved and respected.

| Provider | Setting key | Model key | Models |
|---|---|---|---|
| OpenAI | `openai_api_key` | `openai_model` | gpt-5.4-mini, gpt-5.4, gpt-4.1-mini |
| Claude | `claude_api_key` | `claude_model` | claude-sonnet-4-6 (default), claude-opus-4-6, claude-haiku-4-5-20251001 |
| Gemini | `gemini_api_key` | `gemini_model` | gemini-2.0-flash (default), gemini-2.0-flash-lite, gemini-1.5-pro, gemini-1.5-flash |

- API: OpenAI uses `/v1/responses`; Claude uses Anthropic Messages API `/v1/messages` with `system` + `messages`; Gemini uses `/v1beta/models/{model}:generateContent` with `system_instruction` + `contents`
- All share the same `Provider_Interface::generate_response(['instructions', 'input'])`
- Provider rows in admin settings are shown/hidden by JS based on the selected provider dropdown
- `sanitize_settings` validates against `['openai', 'claude', 'gemini']`, defaults to `openai`

## Token Efficiency (do not regress)

- `short_description` trimmed to 30 words, `description` to 50 words
- History HTML-stripped before prompt; capped at 8 saved / 6 used; each entry max 1000 chars

## Known Gaps

- Rate limiter is sliding window (transient TTL resets per request), not fixed window
- `call_ai_model()` instantiates `Settings` internally — no DI
- Product search is keyword-only (no embeddings)
- Enquiry CPT `aiwoo_enquiry` stores data but has no bulk export
- IP blocklist uses `REMOTE_ADDR` only — if behind a reverse proxy, configure real-IP at server level
