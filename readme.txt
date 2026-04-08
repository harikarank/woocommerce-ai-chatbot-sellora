=== WooCommerce AI Chatbot & Shopping Assistant - Sellora AI ===
Contributors: selloraii
Tags: ai chatbot, woocommerce chatbot, shopping assistant, openai, product recommendations
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WooCommerce chatbot with OpenAI, Claude & Gemini. Smart product search, enquiry capture, full chat history, and abuse protection.

== Description ==

**Sellora AI** is a production-grade WooCommerce AI chatbot that turns browser visits into buying decisions. Customers ask a question in plain language and instantly receive product recommendations drawn from your real catalog — no hallucinated products, no generic answers.

When no match is found, an inline enquiry form captures the lead so your team can follow up. Every conversation is logged so you can see exactly what customers are asking.

= Why Sellora AI? =

Most chatbot plugins send a large chunk of your catalog to the AI on every message, wasting tokens and money. Sellora AI supports **MCP tool-calling mode**, where the AI fetches only the product data it actually needs — drastically cutting token costs while improving accuracy.

= Multi-Provider AI =

Pick the provider that fits your budget. Switch at any time from the settings panel:

* **OpenAI** — GPT-5.4 Mini (default), GPT-5.4, GPT-4.1 Mini
* **Claude (Anthropic)** — Claude Sonnet 4.6 (recommended for MCP), Claude Opus 4.6, Claude Haiku 4.5
* **Gemini (Google)** — Gemini 2.5 Flash (default), Gemini 2.5 Pro, Gemini 2.5 Flash-Lite, Gemini 2.0 Flash-Lite, Gemini 1.5 Pro, Gemini 1.5 Flash

Only enter the API key for the provider you choose — the settings panel hides the irrelevant fields automatically.

= Smart Product Discovery =

The chatbot understands natural queries like:

* "Best phones under ₹20,000"
* "Show me running shoes in size 9"
* "Is this in stock?"
* "Compare these two products"

Sellora AI searches your WooCommerce catalog for the best matches and gives the AI real product data — so every answer is grounded in your actual inventory.

= MCP Tool-Calling Mode =

Enable **MCP mode** under Settings → AI Intelligence to let the AI fetch product data on demand instead of receiving it all upfront:

* `get_products` — search catalog by keyword
* `get_product_details` — full product info by ID
* `get_related_products` — WooCommerce upsells and cross-sells
* `get_user_context` — personalised context: viewed products, search history, cart

Each tool result is cached (2 minutes) and capped (3 calls per tool per request) to keep costs predictable. Works best with Claude models.

= Personalised Recommendations =

When personalisation is enabled, Sellora AI tracks each visitor's:

* Recently viewed products
* Chat search history

This context is sent to the AI via the `get_user_context` tool so it can tailor recommendations without storing personal data server-side.

= Product Card Display =

Control exactly what appears on each recommended product card:

* Product thumbnail image
* Price
* Stock status (In stock / Out of stock / On backorder)
* Short description
* View details link

All five fields are off by default for a clean, compact look. Toggle each on under Settings → Widget → Product Cards.

= Fully Customisable Widget =

Tailor every visual element from the Settings → Appearance tab with 20+ color pickers:

* Accent color and hover state
* Panel and messages area background
* Header background and text
* User and agent message bubble colors
* Send button with hover state
* Input field colors
* Body and secondary text
* Panel border, header border, form border

Additional widget options:

* Panel title and subtitle
* Company logo in the chat header (defaults to Sellora AI logo)
* Custom launcher button icon (defaults to Sellora AI favicon)
* Employee avatar shown next to each assistant reply
* Welcome message shown when chat opens
* Configurable corner radius (0–24 px)
* Configurable maximum message length (default: 200 characters)

= Quick Replies =

Define keyword-based rules that bypass the AI entirely — ideal for FAQs like store hours, return policy, or shipping info. Match types: exact, contains, or prefix. Quick Replies fire before any catalog search or AI call, saving tokens on common questions.

69 default rules are seeded automatically on activation.

= Security & Abuse Protection =

* **IP Blocklist** — Block exact IPs and CIDR ranges (IPv4 + IPv6). Blocked visitors never see the widget; all AJAX requests are rejected server-side.
* **Auto IP Block** — Messages exceeding the length limit automatically add the sender to the blocklist.
* **Bot Detection** — 17 known crawler User-Agent signatures blocked before any AI call. Empty UAs also rejected.
* **Fixed-Window Rate Limiting** — 15 requests per 60-second window per IP, on both chat and enquiry endpoints.
* **Enquiry Honeypot** — Invisible field silently rejects bot form submissions.
* **Nonce Verification** — All AJAX endpoints require a valid WordPress nonce.
* **Input Sanitisation** — All inputs sanitised with `sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `absint`, `sanitize_hex_color`, `esc_url_raw`.
* **Safe Error Handling** — AI provider errors never forwarded to the browser; users see a generic message.
* **AI Error Log** — All AI failures are logged in a private admin-only table. Frontend users never see error details.
* **CSV Injection Protection** — Top Requests CSV export neutralises formula characters.
* **Capability Checks** — All admin pages require `manage_options`.

= Admin Dashboard =

**Chat History** — Browse and search every conversation. Filter by IP, customer name, or date range. Drill into individual sessions.

**AI Error Log** — See every AI failure with full error details, the triggering message, and whether the user received a response or a fallback. Latest errors shown first.

**Enquiries** — View, filter, and manage all lead form submissions captured when no products matched.

**Top Requests** — Analytics on the most-asked questions. One-click "Save as Quick Reply" to automate common AI answers.

**Quick Replies** — Add, edit, and delete keyword rules from a clean admin interface.

**IP Blocklist** — Manage blocked addresses and ranges.

**Plugin Guide** — In-plugin documentation covering all features, configuration steps, and best practices.

**Settings** — Tabbed configuration: General, Widget, Appearance, AI & Prompt, AI Intelligence.

= Requirements =

* WordPress 6.4 or later
* WooCommerce 7.8 or later
* PHP 8.0 – 8.4
* An API key for your chosen AI provider

== Third-Party Services ==

Sellora AI connects to external AI APIs to generate responses. By using this plugin you agree to the terms of service of your chosen provider. **No data is sent to any third-party service until you enter an API key and a visitor sends a chat message.**

= OpenAI =

When the OpenAI provider is selected, each chat message (including the conversation history and relevant product context) is sent to the OpenAI API.

* Service: OpenAI API
* Endpoint: `https://api.openai.com/v1/responses`
* Privacy Policy: https://openai.com/policies/privacy-policy
* Terms of Use: https://openai.com/policies/terms-of-use

= Claude (Anthropic) =

When the Claude provider is selected, each chat message is sent to the Anthropic Messages API.

* Service: Anthropic API
* Endpoint: `https://api.anthropic.com/v1/messages`
* Privacy Policy: https://www.anthropic.com/privacy
* Terms of Use: https://www.anthropic.com/legal/consumer-terms

= Gemini (Google) =

When the Gemini provider is selected, each chat message is sent to the Google Generative Language API.

* Service: Google Generative Language API
* Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`
* Privacy Policy: https://policies.google.com/privacy
* Terms of Use: https://ai.google.dev/gemini-api/terms

**What is sent:** The visitor's sanitised chat message, the last 6 turns of conversation history (HTML-stripped), relevant product data from your WooCommerce catalog (names, prices, stock status, descriptions), and your configured system prompt instructions. API keys are stored server-side and never exposed to visitors.

== Installation ==

**Option A — Upload via WordPress Admin**

1. Download the plugin zip file.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip and click **Install Now**, then **Activate**.

**Option B — Manual FTP / SFTP**

1. Extract the zip file.
2. Upload the `woocommerce-ai-chatbot-sellora` folder to `/wp-content/plugins/`.
3. Activate via **Plugins → Installed Plugins**.

**First-time configuration**

1. Go to **Sellora AI → Settings** in the WordPress admin sidebar.
2. On the **General** tab, choose your AI provider (OpenAI, Claude, or Gemini).
3. Enter the API key for the selected provider.
4. Select a model and adjust temperature if needed.
5. Switch to the **Widget** tab to upload your company logo, assistant avatar, and set the welcome message.
6. Switch to the **Appearance** tab to customise colors to match your brand.
7. Enable the widget with the checkbox on the **General** tab and save.
8. Visit your storefront — the chat widget appears in the bottom-right corner.

== Frequently Asked Questions ==

= Which AI providers are supported? =

Three providers are fully integrated:

* **OpenAI** — `gpt-5.4-mini` (default), `gpt-5.4`, `gpt-4.1-mini`
* **Claude (Anthropic)** — `claude-sonnet-4-6` (default), `claude-opus-4-6`, `claude-haiku-4-5-20251001`
* **Gemini (Google)** — `gemini-2.5-flash` (default), `gemini-2.5-pro`, `gemini-2.5-flash-lite`, `gemini-2.0-flash-lite`, `gemini-1.5-pro`, `gemini-1.5-flash`

You only need an API key for the provider you select. Switch providers at any time from Settings → General.

= Where do I get an API key? =

* **OpenAI** — https://platform.openai.com/api-keys
* **Anthropic (Claude)** — https://console.anthropic.com/account/keys
* **Google (Gemini)** — https://aistudio.google.com/app/apikey

= Does this plugin require WooCommerce? =

The plugin activates without WooCommerce, but product-aware answers and catalog search only work when WooCommerce is installed and active. Without WooCommerce, the chat widget still loads but will not surface product recommendations.

= Is my API key secure? =

Yes. API keys are stored in the WordPress database and never output to the frontend HTML, JavaScript, or AJAX responses. All AI calls are made server-side via WordPress's `wp_remote_post`.

= What data is sent to the AI provider? =

The visitor's sanitised message, up to 6 turns of conversation history (HTML-stripped), relevant product data from your catalog, and your custom system prompt instructions. No personally identifiable information is sent unless a visitor explicitly includes it in their message.

= What is MCP tool-calling mode? =

In standard (legacy) mode, Sellora AI injects a set of products into every AI prompt. In MCP mode, the AI receives no product data upfront — instead it calls tools to fetch only the data it needs. This reduces token usage, improves answer accuracy, and supports personalisation. Enable it under Settings → AI Intelligence.

= How do I block a specific IP address? =

Go to **Sellora AI → IP Blocklist**. Enter an exact IP (IPv4 or IPv6) or a CIDR range such as `192.168.1.0/24`. The widget immediately disappears for matching visitors and all their AJAX requests are rejected silently.

= What happens when a customer message is too long? =

Messages exceeding the configured limit (default: 200 characters) are rejected and the sender's IP is automatically added to the blocklist to protect your token budget. Adjust the limit under Settings → General → Max message length.

= Will bots waste my API quota? =

No. Before any AI call: IP blocklist check → bot User-Agent detection (17 signatures + empty UA) → nonce verification → fixed-window rate limiting (15 req / 60 s per IP) → message length check with auto-block. Enquiry form submissions are also rate-limited and honeypot-protected.

= How do Quick Replies work? =

Quick Replies are keyword rules that return a canned response without calling the AI at all. Define them under Sellora AI → Quick Replies. Supported match types: exact (full message match), contains (keyword anywhere), prefix (message starts with keyword). 69 default rules are included.

= Where are chat logs and enquiries stored? =

Chat logs are stored in a custom database table `{prefix}aiwoo_chat_logs` created on plugin activation. Enquiries are stored as private WordPress posts (`aiwoo_enquiry`). Both are removed when the plugin is deleted.

= How do I customise the chat colors? =

Go to Settings → Appearance. There are 20+ color pickers covering every element of the widget. Leave any field blank to use the built-in default.

= How do I see what AI errors have occurred? =

Go to **Sellora AI → AI Error Log**. Every AI failure is recorded there — including the user message, the error detail, and whether it was a hard failure (user saw an error) or a soft fallback (user saw product cards). This page is only visible to admins.

= Can I customise what information shows on product cards? =

Yes. Go to Settings → Widget → Product Cards. You can individually enable: price, stock status, thumbnail image, short description, and a "View details" link. All are off by default for a clean look.

= Is the plugin GPL compatible? =

Yes. The plugin is released under GPLv2 or later. All dependencies are loaded via WordPress APIs (no external JS libraries injected). All AI calls use `wp_remote_post`.

= Does the plugin store personal data? =

The plugin stores: chat messages (text only), IP addresses, and optionally customer names (backfilled from enquiry form submissions). No passwords, payment data, or sensitive personal information is stored. All data is held in your own WordPress database and removed on plugin deletion.

== Screenshots ==

1. Floating chat widget on the storefront — clean, branded, responsive.
2. Product recommendation cards displayed inline with the AI response.
3. Enquiry form shown automatically when no products match.
4. Settings panel — General tab with AI provider selection.
5. Settings panel — Appearance tab with full color customization.
6. Settings panel — AI Intelligence tab with MCP tool-calling options.
7. Chat History admin page with session list and filters.
8. Quick Replies admin page with keyword rules.
9. AI Error Log admin page showing recent failures.

== Changelog ==

= 1.0.0 =

Initial release.

**AI Providers**

* OpenAI GPT integration — models: gpt-5.4-mini, gpt-5.4, gpt-4.1-mini
* Claude (Anthropic) integration — models: claude-sonnet-4-6, claude-opus-4-6, claude-haiku-4-5-20251001
* Gemini (Google) integration — models: gemini-2.5-flash, gemini-2.5-pro, gemini-2.5-flash-lite, gemini-2.0-flash-lite, gemini-1.5-pro, gemini-1.5-flash
* Modular provider interface — swap between providers from the settings panel without losing configuration

**MCP Tool-Calling**

* MCP mode — AI fetches product data on demand via tool calls instead of receiving it all upfront
* Four tools: get_products, get_product_details, get_related_products, get_user_context
* Per-tool call caps (3/tool/request) and transient caching (120 s) for cost control
* Max 5 API round trips per chat turn

**Personalisation**

* Viewed products tracked per browser session (sessionStorage, no server-side storage)
* Search history tracked per browser session
* Personalisation context exposed to AI via get_user_context tool when enabled

**Chat Widget**

* Floating animated launcher button with custom or default SVG icon
* Responsive panel — full-screen on mobile, floating on desktop
* Company logo in panel header with SVG default fallback
* Configurable welcome message
* Configurable message length limit (default: 200 chars) with live character counter
* Send button disabled when input is empty
* Auto-expand textarea up to 5 lines
* Product recommendation cards with optional price, stock, image, description, view link
* Enquiry form rendered inside chat when no products match
* Chat state and session history persisted to sessionStorage
* Clear chat button removes history and starts fresh session

**Admin Settings (Tabbed)**

* General — provider, API keys, model selection, temperature, catalog size, message length limit
* Widget — panel title/subtitle, company logo, avatar, launcher icon, welcome message, no-match text, product card toggles, enquiry form text
* Appearance — 20+ color pickers covering every widget element; corner radius; form border color
* AI & Prompt — additional system prompt instructions
* AI Intelligence — MCP mode, max products per tool call, personalisation, upsell/cross-sell

**Admin Pages**

* Chat History — paginated session list with filters (IP, name, date range); drill-down session detail
* AI Error Log — private log of all AI failures with error context and details; latest first
* Enquiries — filterable list of submitted leads (name, email, date)
* IP Blocklist — add/delete exact IPs and CIDR ranges (IPv4 and IPv6)
* Quick Replies — keyword rule editor with add/edit/delete
* Top Requests — analytics on most-asked questions; one-click "Save as Quick Reply"; CSV export
* Plugin Guide — in-plugin documentation for all features
* Settings — tabbed configuration panel

**Quick Replies**

* Rule-based keyword matching that bypasses AI entirely
* Match types: exact, contains, prefix
* Priority ordering; active/inactive toggle
* 69 default rules seeded on activation
* Transient cache with automatic flush on any CRUD operation

**Security**

* IP blocklist with CIDR support (IPv4 + IPv6); enforced on widget render and all AJAX endpoints
* Automatic IP block on oversized messages
* Bot User-Agent detection — 17 known signatures blocked before nonce check
* Nonce-verified AJAX endpoints
* Fixed-window rate limiting — 15 req / 60-second window per IP; independent windows
* Rate limiting applied to both chat and enquiry endpoints
* Enquiry honeypot field
* Input sanitisation on all AJAX inputs and settings fields
* API keys never output to frontend
* AI provider exceptions never forwarded to browser
* CSV formula injection protection on Top Requests export
* Capability checks on all admin pages and admin-post handlers

**Chat Logging**

* Custom database table for chat logs — created on activation, removed on deletion
* Session grouping with filters (IP, name, date range)
* Customer name backfilled from enquiry form submissions
* AI error log — separate table for failed/degraded AI responses; admin-only

**Branding**

* Custom SVG logo and favicon used throughout the admin backend
* Logo displayed in all admin page headers replacing plain text
* Logo and favicon shown in WordPress admin sidebar menu and topbar

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.
