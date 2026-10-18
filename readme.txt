=== RuleHook: Advanced Shipping Rules for WooCommerce ===
Contributors: waseem_senjer, wprubyplugins, rulehook
Donate link: https://rulehook.com
Tags: woocommerce, shipping, dynamic shipping, rule-based shipping
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 2.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your WooCommerce store to RuleHook — a powerful rules engine for dynamic shipping rates.

== Description ==

**RuleHook Connector** seamlessly integrates WooCommerce with [RuleHook](https://rulehook.com), a SaaS platform that gives you precise control over shipping logic.

Out of the box, WooCommerce shipping rules are limited. With RuleHook, you can define **unlimited conditions and actions** to tailor shipping methods to your exact business needs.

Examples of what you can do:
– Offer *Free Shipping* for orders over $100 **except** bulky items.
– Apply different rates based on *weight brackets, volume, or dimensions*.
– Restrict certain methods for specific *countries, states, or zip codes*.
– Add handling fees or surcharges dynamically.
– Show or hide methods depending on *user role, product category, or cart contents*.

If WooCommerce shipping zones frustrate you, RuleHook Connector unlocks the flexibility you need — without hacks or bloated plugins.

== Features ==

– Full integration with RuleHook SaaS.
– Unlimited shipping rules with AND/OR logic.
– Supports weight, subtotal, dimensions, quantity, product tags, categories, attributes, user roles, locations, and more.
– Fine-grained control: show, hide, rename, or adjust shipping methods dynamically.
– Fast evaluation engine built for scale.
– Works with existing WooCommerce shipping zones.

👉 Learn more and create your first rules at [RuleHook.com](https://rulehook.com/?utm_source=wordpress&utm_medium=plugin&utm_campaign=readme_link)


== Integration & Source Code ==

This plugin connects your WooCommerce store to the RuleHook platform (https://rulehook.com), a service that provides dynamic shipping rate logic based on custom conditions and rules.

When connected, the plugin communicates with the RuleHook API to:
- Authenticate your WooCommerce store.
- Sync shipping zones, shipping methods, and order/cart data when evaluating rules.
- Retrieve calculated shipping rates that match your configured rules on the RuleHook dashboard.

**Data Sent**

The plugin may send the following data to the RuleHook API during operation:
- Store URL and store name (for identification)
- Shipping zones, methods, and related settings
- Destination country, state, postcode, and cart details (for rate calculation)
- API credentials generated during the “Authorize This Site” process
- Only if you turn on Shipping Margin in RuleHook: for each order, when it is placed and when it is completed, refunded or cancelled, an irreversible order reference (a hash of the order id with a secret that stays in your store), the shipping rate chosen and what was charged for it, order value and weight totals, product ids and quantities, the destination country, state and postcode prefix, and timestamps. Names, email addresses, street addresses and coupon codes are never sent.

No customer personal data (such as names, email addresses, or phone numbers) is transmitted.

**Purpose**

This data exchange is required for the plugin to calculate and display dynamic shipping rates in WooCommerce based on your rules defined in RuleHook.

**Service Provider**

The external service is provided by **RuleHook**

- [Terms of Service](https://rulehook.com/terms-of-service)
- [Privacy Policy](https://rulehook.com/privacy-policy)

**Source Code**

The full source code for this plugin, including unminified JavaScript and CSS files, is publicly available on GitHub:
https://github.com/wsenjer/rulehook-connector/


== Installation ==

1. Upload the `rulehook` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Go to **WooCommerce → Settings → Shipping → RuleHook**.
5. Connect your store to [RuleHook](https://rulehook.com) and start creating rules.

== Frequently Asked Questions ==

= Do I need a RuleHook account? =
Yes. The plugin connects your WooCommerce store to the RuleHook service. Sign up at [RuleHook.com](https://rulehook.com).

= Can I use this without the SaaS? =
No, the plugin is just the connector. All rule management happens inside your RuleHook account.

= Will this slow down checkout? =
No. RuleHook is designed for performance. Rules are evaluated quickly and cached to keep checkout smooth.

= Can I migrate existing WooCommerce shipping methods? =
You can recreate your existing methods as RuleHook rules, then extend them with advanced conditions.

== Screenshots ==

1. RuleHook dashboard with rule editor.
2. Example of dynamic shipping rates at checkout.

== Changelog ==

= Unreleased =
* Added: "Import past orders" for Shipping Margin, on the RuleHook settings page. Orders from the last 13 months are sent in the background, in batches, with the same fields as new orders and no customer names, emails, phone numbers or street addresses: completed, refunded and cancelled orders as they stand, and processing and on-hold orders as placed. Imported orders don't count towards your monthly order limit in RuleHook.
* Changed: an order placed before Shipping Margin was turned on is reported as imported rather than live when its status changes later, so it doesn't count towards your monthly order limit.

= 2.1.0 =
* Added: order outcomes for RuleHook's Shipping Margin. Once you turn on Shipping Margin in RuleHook, the connector reports each order's chosen shipping rate, what was charged for it, and its value and weight totals when the order is placed, completed, refunded or cancelled. Until then nothing is sent.
* Added: outcomes are sent in the background with Action Scheduler, so checkout never waits for RuleHook. A failed send is retried four times over about two and a half hours, then dropped.
* Privacy: order ids are hashed with a secret that never leaves your store, and only the destination's country, state and postcode prefix are sent. Names, emails and addresses never are.
* Fixed: a RuleHook API error no longer triggers an "Array to string conversion" PHP warning.

= 2.0.0 =
* Added: rules can hide, rename and reprice rates from your other shipping plugins (for example Australia Post live rates), once you turn on rules for store rates in RuleHook's store settings. Until then the connector works exactly as before.
* Added: with store rates on, RuleHook is called once per shipping calculation, after every shipping plugin has added its rates. Repriced rates get their taxes recalculated.
* Added: if RuleHook is unreachable, slow (3 seconds by default, adjustable with the `rulehook_external_rates_timeout` filter) or returns an error, your store's own rates are shown unchanged.
* Added: shipping zones and their methods are included in the store sync, so rules can target them before your first order.
* Added: a "Scan shipping rates" button that finds the services your shipping plugins offer in each zone.
* Added: the `RULEHOOK_API_URL` constant and `rulehook_api_url` filter, to point the connector at another RuleHook server.
* Changed: with store rates on, "Hide other methods" runs in RuleHook and keeps the store rates the rule targets, instead of hiding every non-RuleHook rate.

= 1.1.1 =
* Fixed: shipping rate subtitles showed raw HTML (`<br><small>`) instead of formatted text.
* Fixed: every rate with a subtitle showed the same subtitle; each rate now shows its own.
* Fixed: subtitles disappeared when WooCommerce served cached shipping rates.
* Removed: the connector-side "rename method" action, which never ran. Renamed labels already come from RuleHook in the rate itself.

= 1.1.0 =
* Added: product categories and stock status to sync payload.
* Added: product attributes to sync payload.
* Fixed: Refactor session handling to use transients and add weight/dimension units to payload.

= 1.0.1 =
* Update primary and secondary theme colors for rebranding

= 1.0.0 =
* Initial Release.


== Upgrade Notice ==

= 2.1.0 =
Adds order reporting for RuleHook's Shipping Margin. Nothing is sent until you turn it on in RuleHook.

= 2.0.0 =
Lets RuleHook rules act on rates from your other shipping plugins. Nothing changes until you turn this on in RuleHook's store settings.

= 1.0.0 =
First public release of RuleHook Connector.
