=== Site Visibility Manager For WooCommerce ===
Contributors: storecrafted
Donate link: https://storecrafted.com
Tags: woocommerce, coming soon, site visibility, abilities-api, mcp
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Granular public allowlist for WooCommerce Coming Soon — open homepage and blog while the shop stays closed.

== Description ==

**Site Visibility Manager For WooCommerce** extends WooCommerce’s built-in Site Visibility / Coming Soon mode so you can choose *exactly* which parts of the site stay public while everything else remains behind Coming Soon.

You keep designing the **Page: Coming soon** template in the Site Editor. This plugin only decides which front-end requests are allowlisted.

= Why you may want this plugin =

WooCommerce Site Visibility is either **entire site** or **store pages only**. Many launches need a middle option: keep building or refining the storefront, but still publish content the public can read.

Common situations:

* You are still finishing products, pricing, or checkout, but want the **blog open** so visitors can follow build updates, launch news, or SEO content.
* You want a public **homepage** (brand story, waitlist, press) while the **shop, products, cart, and checkout** stay gated.
* You need a few specific pages public (About, Contact, Lookbook) without opening the whole non-store site.
* Agencies staging a content-led launch want Woo-native Coming Soon — not a separate maintenance-mode plugin that replaces Woo’s template.

Without this plugin, “store pages only” opens *all* non-store URLs. Site Visibility Manager lets you open **selected** sections and URLs instead.

= Recommended launch setup =

1. Go to **WooCommerce → Settings → Site visibility**
2. Set **Coming soon** and leave **Apply to store pages only** off (entire site)
3. Open **WooCommerce → Settings → Site Visibility Manager**
4. Enable **Homepage**, **Blog index**, and **Blog posts** (and any extra pages)
5. Customise **Templates → Page: Coming soon** in the Site Editor

= Features =

* Public section presets (homepage, blog, shop, products, cart, checkout, account, and more)
* Searchable page and post pickers
* Public post types and taxonomies (products stay behind dedicated commerce presets)
* Status panel with links to Site Visibility and the Coming Soon template
* Developer filters: `svm_is_request_public`, `svm_allowlist_settings`
* WordPress Abilities API — read Coming Soon / allowlist status and update the allowlist with the same `manage_woocommerce` capability as the settings UI
* Works with block and classic themes WooCommerce Coming Soon already supports

= Abilities API and MCP Adapter =

This plugin registers WordPress Abilities so agents and automation can inspect and update the public allowlist (same sanitisation and capability checks as the settings screen).

To connect MCP-compatible AI clients to those abilities, install the free [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin from WordPress.org. MCP Adapter bridges the Abilities API to the Model Context Protocol; it is optional and is **not** bundled or required to use Site Visibility Manager’s settings UI.

Permission checks still apply: only users with `manage_woocommerce` can run these abilities.

= What this plugin does not do =

* It does not replace WooCommerce Coming Soon with a custom landing-page builder
* It does not block the REST API or Store API (template path only)
* It is not a general WordPress maintenance-mode plugin for sites without WooCommerce
* It does not require MCP Adapter — install MCP Adapter only if you want MCP clients to use the abilities

== Installation ==

1. Upload the plugin zip via **Plugins → Add New → Upload Plugin**, or install from WordPress.org.
2. Activate **Site Visibility Manager For WooCommerce**. WooCommerce must already be active.
3. Configure public access under **WooCommerce → Settings → Site Visibility Manager**.
4. Enable Coming Soon under **WooCommerce → Settings → Site visibility**.

== Requirements == 

* WordPress 7.0+
* PHP 8.1+
* WooCommerce 11.0+

Author: [StoreCrafted](https://storecrafted.com/)

Full Plugin Source: [Github](https://github.com/storecraftedhq/site-visibility-manager/)

== Frequently Asked Questions ==

= Why are my settings not changing the live site? =

The allowlist only applies when WooCommerce Site Visibility is set to **Coming soon**. If the site is **Live**, settings save but do not gate the front end.

= Should I use “Apply to store pages only”? =

For the common “homepage + blog public, shop closed” launch, use **Coming soon** for the **entire site**, then open sections with this plugin. Store-pages-only already leaves non-store URLs public, so blog presets are largely unnecessary in that mode.

= Do child pages open when I allowlist a parent? =

No. Only the exact selected page IDs are public.

= Does the private share link still work? =

Yes. WooCommerce’s private share link and users with `manage_woocommerce` continue to bypass Coming Soon as usual.

= Does this hide products from the REST API? =

No. v1 focuses on the Coming Soon template path. API visibility is unchanged.

= Does this work with the WordPress MCP Adapter? =

Yes. Site Visibility Manager registers Abilities API endpoints for allowlist status and settings. Install [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) if you want MCP clients to call those abilities. MCP Adapter is optional; the React settings tab works without it.

== Screenshots ==

1. Site Visibility Manager React settings tab with Site Visibility status and public section presets.
2. Searchable public pages picker on the settings screen.

== Changelog ==

= 1.0.0 =
* Initial public release: full public allowlist for WooCommerce Coming Soon, settings UI, and developer filters.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Requires WooCommerce 11.0+.
