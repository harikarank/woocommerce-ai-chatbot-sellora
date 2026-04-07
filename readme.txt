=== WooCommerce AI Chatbot & Shopping Assistant - Sellora AI ===
Contributors: selloraii
Tags: ai chatbot, woocommerce chatbot, shopping assistant, openai, product recommendations, ai, ecommerce, chatbot
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Tested PHP up to: 8.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WooCommerce chatbot and shopping assistant that helps customers find products, get instant answers, and boost sales with smart recommendations.

== Description ==

**Sellora AI** is a powerful WooCommerce AI chatbot and shopping assistant designed to increase conversions, improve customer experience, and automate product discovery in your store.

With seamless AI integration, Sellora AI allows your customers to chat naturally, discover products faster, and receive intelligent recommendations — all in real time.

= Key Features =

**AI Chatbot for WooCommerce**

Enable a smart chatbot that understands customer queries like:

* Best phones under ₹20,000
* Show me running shoes
* Do you have this in stock?

The chatbot responds instantly with relevant products and answers.

**Smart Product Recommendations**

Sellora AI searches your WooCommerce catalog and surfaces the most relevant products for each customer query — no manual configuration needed.

**Customisable Chat Widget**

* Set your own panel title and subtitle
* Upload your company logo for the chat header
* Upload an employee avatar for assistant replies
* Choose your brand accent colour
* Upload a custom launcher icon

**Enquiry Form Fallback**

When no matching products are found, the chatbot presents a contact form so customers can share their requirements. Enquiries are emailed to the store admin and saved in WordPress for review.

**Bot Protection & Rate Limiting**

* Known crawler and bot User-Agents are blocked before any AI call is made
* Anonymous IP-based rate limiting prevents burst abuse
* Nonce-verified AJAX requests

**Provider-Ready Architecture**

Built with a clean provider abstraction layer. OpenAI (GPT) is fully integrated. The architecture is ready for additional AI providers in future releases.

= Requirements =

* WordPress 6.4 or later
* WooCommerce 7.8 or later
* PHP 8.0 – 8.4 (all patch versions supported)
* An OpenAI API key

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress **Plugins** screen.
3. Go to **Settings > Sellora AI**.
4. Enter your OpenAI API key and select a model.
5. Optionally upload your company logo, employee avatar photo, and set your panel title and subtitle.
6. Confirm the widget is enabled and visit your storefront.

== Frequently Asked Questions ==

= Does this require WooCommerce? =

The plugin activates without WooCommerce, but product-aware answers and catalog search only work when WooCommerce is installed and active.

= Which AI models are supported? =

OpenAI models are fully supported: `gpt-5.4-mini`, `gpt-5.4`, and `gpt-4.1-mini`. The architecture is prepared for additional providers.

= Will bots or crawlers waste my API quota? =

No. Sellora AI blocks requests from known bots and crawlers by User-Agent before any AI call is made. IP-based rate limiting (15 requests per minute) provides an additional layer of protection.

= Can I customise the chat panel appearance? =

Yes. From the settings page you can set the panel title, subtitle, company logo, employee avatar, accent colour, and launcher icon — all without touching code.

= Where are enquiries stored? =

Enquiries are emailed to the WordPress admin address and also saved as private posts in WordPress (not visible in any admin list by default). A full enquiry management UI is planned for a future release.

== Changelog ==

= 1.0.0 =
* Initial release of Sellora AI
* OpenAI-powered product-aware chat widget
* Customisable panel header (title, subtitle, company logo)
* Per-message employee avatar and user avatar
* Bot User-Agent detection
* IP-based rate limiting
* Enquiry form fallback with email notification and WordPress storage
