=== WooCommerce AI Chatbot & Shopping Assistant - Sellora AI ===
Contributors: selloraii
Tags: ai chatbot, woocommerce chatbot, shopping assistant, openai, claude, gemini, product recommendations, ai, ecommerce, chatbot
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Tested PHP up to: 8.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WooCommerce chatbot supporting OpenAI, Claude (Anthropic), and Gemini (Google). Helps customers find products, get instant answers, and boost sales with smart recommendations.

== Description ==

**Sellora AI** is a production-grade WooCommerce AI chatbot and shopping assistant designed to increase conversions, improve customer experience, and automate product discovery — without writing a single line of code.

Customers type naturally and receive intelligent product recommendations instantly. When no match is found, a smart enquiry form captures their details so your team can follow up.

= Multi-Provider AI =

Choose the AI provider that best fits your needs and budget. All three are fully integrated:

* **OpenAI** — GPT-5.4 Mini, GPT-5.4, GPT-4.1 Mini
* **Claude (Anthropic)** — Claude Sonnet 4.6, Claude Opus 4.6, Claude Haiku 4.5
* **Gemini (Google)** — Gemini 2.0 Flash, Gemini 2.0 Flash-Lite, Gemini 1.5 Pro, Gemini 1.5 Flash

Switch between providers at any time from the settings panel. Enter the API key for your chosen provider and you are live.

= Smart Product Discovery =

The chatbot understands customer queries like:

* "Best phones under ₹20,000"
* "Show me running shoes in size 9"
* "Do you have this in stock?"

Sellora AI searches your WooCommerce catalog for the most relevant matches and sends real product data to the AI — so responses are always grounded in your actual inventory, not hallucinated.

= Fully Customisable Widget =

Tailor every visual element from the Settings → Appearance tab:

* Accent colour, hover colour
* Panel and messages area background
* Header background and text colour
* User and agent message bubble colours
* Send button colours including hover state
* Input field colours
* Body and secondary text colours
* Border colour

Additional widget options:

* Panel title and subtitle
* Company logo in the chat header
* Employee avatar shown next to each assistant reply
* Custom launcher button icon
* Welcome message shown when the chat opens
* Configurable maximum message length (default: 200 characters)

= Organised Settings with Tabs =

The settings panel is organised into four focused tabs:

* **General** — AI provider, API keys, model selection, message limits
* **Widget** — Branding, copy, images, welcome message
* **Appearance** — Complete colour customisation (17 colour fields)
* **AI & Prompt** — Additional system prompt instructions

= Security & Abuse Protection =

* **IP Blocklist** — Block specific IP addresses or CIDR ranges (IPv4 and IPv6). Blocked IPs never see the widget and all their AJAX requests are rejected server-side.
* **Auto IP Block** — If a request exceeds the configured message length limit, the sender's IP is automatically added to the blocklist.
* **Bot Detection** — 17 known bot and crawler User-Agent signatures are blocked before any AI call is made. Empty User-Agent strings are also rejected.
* **Fixed-Window Rate Limiting** — 15 requests per 60-second window per IP, applied to both chat and enquiry endpoints. Windows are truly independent; the limit cannot be evaded by pacing requests.
* **Enquiry Honeypot** — An invisible form field traps bots that auto-fill every input. Legitimate users never see or fill it; bot submissions are silently discarded without storing or emailing anything.
* **Nonce Verification** — All AJAX endpoints require a valid WordPress nonce.
* **Input Sanitisation** — All inputs are sanitised with the appropriate WordPress functions (`sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `absint`, `sanitize_hex_color`, `esc_url_raw`) before processing.
* **Safe Error Handling** — Internal API and network errors are never forwarded to the browser. Users receive a generic message; full details are available in the PHP error log when `WP_DEBUG_LOG` is enabled.
* **CSV Injection Protection** — The Top Requests CSV export prefixes cells that begin with formula characters (`=`, `+`, `-`, `@`) to prevent formula injection in spreadsheet software.
* **Capability Checks** — All admin pages and admin-post handlers require `manage_options` capability. Nonces are verified before any state change.

= Enquiry Management =

When no matching products are found, Sellora AI shows an inline enquiry form. Submitted enquiries are:

* Emailed to the store admin immediately
* Saved privately in WordPress for later review
* Viewable and filterable in the Sellora AI → Enquiries admin page
* Linked back to the customer's chat session

= Chat History =

Every chat exchange is logged in a dedicated database table (`{prefix}aiwoo_chat_logs`). Browse sessions in the Sellora AI → Chat History admin page, filter by IP, name, or date range, and drill into individual conversations.

= Requirements =

* WordPress 6.4 or later
* WooCommerce 7.8 or later
* PHP 8.0 – 8.4
* An API key for your chosen AI provider (OpenAI, Anthropic, or Google)

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
3. Enter the API key for the selected provider (only the fields for your chosen provider are shown).
4. Select a model and adjust temperature if needed.
5. Switch to the **Widget** tab and upload your company logo, assistant avatar, and set the welcome message.
6. Switch to the **Appearance** tab to customise colours to match your brand.
7. Enable the widget with the checkbox on the **General** tab and save.
8. Visit your storefront — the chat widget appears in the bottom-right corner.

== Frequently Asked Questions ==

= Which AI providers are supported? =

Three providers are fully integrated:

* **OpenAI** — `gpt-5.4-mini` (default), `gpt-5.4`, `gpt-4.1-mini`
* **Claude (Anthropic)** — `claude-sonnet-4-6` (default), `claude-opus-4-6`, `claude-haiku-4-5-20251001`
* **Gemini (Google)** — `gemini-2.5-flash` (default), `gemini-2.5-pro`, `gemini-2.5-flash-lite`, `gemini-2.0-flash-lite`, `gemini-1.5-pro`, `gemini-1.5-flash`

You only need an API key for the provider you select. You can switch providers at any time without losing your other settings.

= Where do I get an API key? =

* **OpenAI** — https://platform.openai.com/api-keys
* **Anthropic (Claude)** — https://console.anthropic.com/account/keys
* **Google (Gemini)** — https://aistudio.google.com/app/apikey

= Does this require WooCommerce? =

The plugin activates without WooCommerce, but product-aware answers and catalog search only work when WooCommerce is installed and active. Without WooCommerce, the chat widget still loads but will not surface product recommendations.

= How do I block a specific IP? =

Go to **Sellora AI → IP Blocklist**. Enter an exact IP address (IPv4 or IPv6) or a CIDR range such as `192.168.1.0/24`. The widget will immediately disappear for matching visitors and all their AJAX requests will be rejected silently.

= What happens when a customer sends a message that is too long? =

Messages exceeding the configured limit (default: 200 characters) are rejected and the sender's IP is automatically added to the blocklist. This protects your AI token budget from automated abuse. You can adjust the limit under **Settings → General → Max message length**.

= Will bots waste my API quota? =

No. Sellora AI applies multiple layers of protection before any AI call is made: IP blocklist check → bot User-Agent detection (17 known signatures + empty UA) → nonce verification → fixed-window rate limiting (15 req / 60 s per IP) → message length check. A legitimate human customer is typically through all checks within milliseconds; a bot is usually rejected at the first layer. Enquiry form submissions are also rate-limited and protected by a honeypot field.

= How do I customise the chat colours? =

Go to **Sellora AI → Settings → Appearance**. There are 17 colour pickers covering every element of the widget: accent colour, hover state, panel backgrounds, header, message bubbles, input field, send button, and text colours. Leave any field blank to use the built-in default.

= Where are chat logs stored? =

Chat exchanges are stored in a custom table `{prefix}aiwoo_chat_logs` created on plugin activation. Logs are viewable under **Sellora AI → Chat History**. The table is removed automatically when the plugin is deleted.

= Where are enquiries stored? =

Enquiries are stored as private WordPress posts (post type `aiwoo_enquiry`) and emailed to the site admin address. They are viewable and filterable under **Sellora AI → Enquiries**.

= Is my API key exposed to visitors? =

No. API keys are stored in the WordPress options table (not autoloaded) and are never output to the frontend HTML or JavaScript. All AI calls are made server-side via WordPress's `wp_remote_post`.

== Changelog ==

= 1.0.0 =

**AI Providers**

* OpenAI GPT integration — models: gpt-5.4-mini, gpt-5.4, gpt-4.1-mini
* Claude (Anthropic) integration — models: claude-sonnet-4-6, claude-opus-4-6, claude-haiku-4-5-20251001
* Gemini (Google) integration — models: gemini-2.0-flash, gemini-2.0-flash-lite, gemini-1.5-pro, gemini-1.5-flash
* Modular provider interface — clean swap between providers from the settings panel

**Chat Widget**

* Floating chat widget with animated launcher button
* Responsive panel — full-screen on mobile, floating on desktop
* Configurable welcome message
* Configurable message length limit (default 200 characters) with live character counter
* Auto-expand textarea up to 5 lines
* Product recommendation cards shown inline with AI response
* Enquiry form rendered inside the chat when no products match
* Chat state and recent history persisted to sessionStorage

**Admin Settings (Tabbed)**

* General — provider, API keys, model, temperature, catalog size, message length limit
* Widget — panel title/subtitle, company logo, assistant avatar, launcher icon, welcome message
* Appearance — 17 colour pickers covering every widget element
* AI & Prompt — additional system prompt instructions

**Admin Pages**

* Chat History — paginated session list with filters (IP, name, date range); drill-down session detail view
* Enquiries — filterable list of submitted enquiries (name, email, date)
* IP Blocklist — add/delete exact IPs and CIDR ranges (IPv4 and IPv6)
* Settings — tabbed configuration panel

**Security**

* IP blocklist — exact IPv4/IPv6 and CIDR ranges; enforced server-side on widget render and all AJAX endpoints
* Automatic IP block on oversized messages (bot signal)
* Bot User-Agent detection — 17 known signatures blocked before nonce check
* Nonce-verified AJAX endpoints
* IP-based rate limiting — 15 requests per sliding minute window
* Input sanitisation on all AJAX inputs and settings fields
* API keys never output to frontend

**Chat Logging**

* Custom database table for chat logs — created on activation, removed on deletion
* Session grouping — browse by session, filter by IP/name/date
* Customer name backfilled from enquiry submissions
