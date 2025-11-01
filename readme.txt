=== RuleHook: Advanced Shipping Rules for WooCommerce ===
Contributors: waseem_senjer, wprubyplugins, rulehook
Donate link: https://rulehook.com
Tags: woocommerce, shipping, dynamic shipping, rule-based shipping
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.1
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
= 1.0.1 =
* Update primary and secondary theme colors for rebranding

= 1.0.0 =
* Initial Release.


== Upgrade Notice ==

= 1.0.0 =
First public release of RuleHook Connector.
