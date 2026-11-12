=== AnythingMCP for WooCommerce ===
Contributors: helpcodeai
Tags: ai, mcp, claude, chatgpt, rest-api
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use your store in Claude, ChatGPT, Copilot and Gemini. Creates a read-only API key and opens the AnythingMCP setup with your store filled in.

== Description ==

Ask your AI assistant about your shop: "Which orders came in today?", "Which products are out of stock?", "What were last month's best sellers?". AnythingMCP for WooCommerce connects your store to Claude, ChatGPT, Microsoft Copilot and Gemini through [AnythingMCP](https://anythingmcp.com), an MCP gateway for business systems.

The plugin adds an **AI Assistants** tab to *WooCommerce → Settings* that sets up the connection in three steps, without code:

1. **Create API key** creates a WooCommerce REST API key for your user, in the same way as WooCommerce's own *Add key* button. It is read-only unless you choose Read/Write. The key and secret are shown once, with copy buttons.
2. **Open AnythingMCP** opens the AnythingMCP setup for WooCommerce in a new tab, with your store address already filled in. You paste the key and secret there and click *Check and install*. AnythingMCP checks the key against your store before it saves anything.
3. AnythingMCP then shows how to add your store to Claude, ChatGPT, Copilot or Gemini.

What the plugin does, and what it does not:

* It sends nothing on its own: no telemetry, no update checks, no external scripts, fonts or images.
* The consumer key and secret are never part of a link and are not stored in plain text by the plugin. WooCommerce keeps only a hash of the key, as for every REST API key.
* Your data stays behind your own API key. Revoke the key and the assistant loses access at once.
* It works with AnythingMCP Cloud (EU, Frankfurt) and with a self-hosted AnythingMCP.

= Requirements =

* WooCommerce 8.0 or later.
* A site on **https://**: AnythingMCP connects only over HTTPS. For AnythingMCP Cloud the site must be reachable from the internet.
* Pretty permalinks (any structure other than *Plain*), so that the REST API answers at `/wp-json/`.
* An administrator or shop manager account.
* An AnythingMCP account: [AnythingMCP Cloud](https://anythingmcp.com/pricing), or your own [self-hosted AnythingMCP](https://github.com/HelpCode-ai/anythingmcp) (open source).

== External services ==

This plugin links to AnythingMCP, a service of helpcode.ai GmbH, Freiburg, Germany. AnythingMCP is the MCP gateway that connects your store to AI assistants.

* **The plugin itself sends no data** to AnythingMCP or anywhere else. It makes no requests from your server and loads nothing from other sites.
* **When you click "Open AnythingMCP"**, your browser opens the AnythingMCP setup page (https://cloud.anythingmcp.com, or the self-hosted address you entered) in a new tab. The link contains your store address (the WordPress home URL), so that the page can fill it in. Nothing else is in the link: never the consumer key or secret.
* **On the AnythingMCP page**, you sign in and paste the consumer key and secret yourself. AnythingMCP stores them encrypted and uses them to call your store's WooCommerce REST API when your AI assistant asks for data. Which data is read or changed depends on what you ask and on the key's permission (Read or Read/Write).

AnythingMCP terms of service: https://anythingmcp.com/agb
AnythingMCP privacy policy: https://anythingmcp.com/datenschutz

With a self-hosted AnythingMCP, the data goes only to your own instance, under your own terms.

== Installation ==

1. Install and activate WooCommerce.
2. Install the plugin from *Plugins → Add New* (search for "AnythingMCP"), or upload the zip under *Plugins → Add New → Upload Plugin*, and activate it.
3. Open *WooCommerce → Settings → AI Assistants*.
4. Choose the permission, click **Create API key** and copy the key and secret.
5. Click **Open AnythingMCP**, sign in or create an account, paste the key and secret and click *Check and install*.
6. Add AnythingMCP to your AI assistant as shown at the end of the setup.

== Frequently Asked Questions ==

= Is the plugin free? =

Yes. The plugin is free and open source (GPLv2 or later). AnythingMCP Cloud is a paid service with a free trial; see the [pricing](https://anythingmcp.com/pricing). AnythingMCP itself is open source and can be self-hosted at no cost.

= What data is sent, and where? =

The plugin sends nothing. When you click "Open AnythingMCP", your browser opens AnythingMCP with your store address in the link. You enter the API key there yourself. From then on, AnythingMCP calls your store's REST API with that key whenever your AI assistant needs data, for example to list orders or look up a product. See the *External services* section for details and links to the terms and privacy policy.

= Should I choose Read or Read/Write? =

Start with **Read**: the assistant can look up orders, products, customers, coupons and reports, but cannot change anything. Choose **Read/Write** if you want the assistant to change prices or stock, update orders, issue refunds or create products and coupons. You can create a second key with a different permission at any time.

= Can I use a self-hosted AnythingMCP? =

Yes. AnythingMCP is open source ([HelpCode-ai/anythingmcp](https://github.com/HelpCode-ai/anythingmcp)). Enter the https:// address of your instance in *WooCommerce → Settings → AI Assistants → Self-hosted AnythingMCP URL*. Leave the field empty to use AnythingMCP Cloud.

= How do I revoke access? =

Go to *WooCommerce → Settings → Advanced → REST API*, find the key named "AnythingMCP (date)" and click *Revoke*. AnythingMCP loses access at once. You can also remove the connector in AnythingMCP.

= What happens when I uninstall the plugin? =

Uninstalling removes the plugin's only setting (the self-hosted AnythingMCP URL). API keys you created stay in WooCommerce, because they belong to WooCommerce and may still be in use: revoke them under *WooCommerce → Settings → Advanced → REST API*.

= Which user does the key belong to? =

The user who clicks "Create API key". WooCommerce checks that user's permissions on every request, so the key can do at most what that user can do. Use an administrator or shop manager.

= My site is not on https:// =

AnythingMCP connects only to stores on HTTPS, because the key is sent with every request. Set up an SSL certificate first; most hosts offer one for free.

== Screenshots ==

1. The AI Assistants tab in WooCommerce → Settings, with the self-hosted AnythingMCP setting.
2. A new API key, shown once with copy buttons.
3. The key in WooCommerce's own list of REST API keys, where you can revoke it.

== Changelog ==

= 1.0.0 =
* First release: AI Assistants tab, API key creation (Read or Read/Write), Open AnythingMCP with the store address filled in, self-hosted AnythingMCP URL.
