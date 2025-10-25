# RuleHook Connector

**Contributors:** [@waseem_senjer](https://profiles.wordpress.org/waseem_senjer/), [@wprubyplugins](https://profiles.wordpress.org/wprubyplugins/), [@rulehook](https://profiles.wordpress.org/rulehook)  
**Tags:** woocommerce, shipping, dynamic shipping, rule-based shipping  
**Requires at least:** 5.0  
**Tested up to:** 6.8  
**Stable tag:** 1.0.0  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**License URI:** [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)  
**Donate link:** [https://rulehook.com](https://rulehook.com)

---

Connect your WooCommerce store to **RuleHook** — a powerful rules engine for dynamic shipping rates.

---

## 🧩 Description

**RuleHook Connector** seamlessly integrates WooCommerce with [RuleHook](https://rulehook.com), a SaaS platform that gives you precise control over shipping logic.

Out of the box, WooCommerce shipping rules are limited. With RuleHook, you can define **unlimited conditions and actions** to tailor shipping methods to your exact business needs.

### Example Use Cases
- Offer *Free Shipping* for orders over $100 **except** bulky items.
- Apply different rates based on *weight brackets, volume, or dimensions*.
- Restrict certain methods for specific *countries, states, or zip codes*.
- Add handling fees or surcharges dynamically.
- Show or hide methods depending on *user role, product category, or cart contents*.

If WooCommerce shipping zones frustrate you, RuleHook Connector unlocks the flexibility you need — without hacks or bloated plugins.

---

## 🚀 Features

- Full integration with RuleHook SaaS.
- Unlimited shipping rules with AND/OR logic.
- Supports weight, subtotal, dimensions, quantity, product tags, categories, attributes, user roles, locations, and more.
- Fine-grained control: show, hide, rename, or adjust shipping methods dynamically.
- Fast evaluation engine built for scale.
- Works with existing WooCommerce shipping zones.

👉 **Learn more:** [RuleHook.com](https://rulehook.com)

---

## 💻 Source Code

The source code for this plugin, including unminified JavaScript and CSS files, is publicly available on GitHub:  
👉 [https://github.com/wsenjer/rulehook-connector/](https://github.com/wsenjer/rulehook-connector/)

---

## ⚙️ Installation

1. Upload the `rulehook` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce → Settings → Shipping → RuleHook**.
4. Connect your store to [RuleHook](https://rulehook.com) and start creating rules.

---

## 🌐 External Services

This plugin connects your WooCommerce store to the RuleHook platform ([https://rulehook.com](https://rulehook.com)), which provides dynamic shipping rate logic based on custom conditions and rules.

### Data Sent
During operation, the plugin may send:
- Store URL and store name (for identification)
- Shipping zones, methods, and related settings
- Destination country, state, postcode, and cart details (for rate calculation)
- API credentials generated during the “Authorize This Site” process

➡️ **No customer personal data** (names, emails, phone numbers) is transmitted.

### Purpose
This data exchange is required for calculating and displaying dynamic shipping rates in WooCommerce based on your RuleHook-defined rules.

### Service Provider
**RuleHook**
- [Terms of Service](https://rulehook.com/terms-of-service)
- [Privacy Policy](https://rulehook.com/privacy-policy)

---

## ❓ Frequently Asked Questions

**Q: Do I need a RuleHook account?**  
Yes. The plugin connects your WooCommerce store to the RuleHook service. Sign up at [RuleHook.com](https://rulehook.com).

**Q: Can I use this without the SaaS?**  
No. The plugin is just the connector. All rule management happens inside your RuleHook account.

**Q: Will this slow down checkout?**  
No. RuleHook is designed for performance. Rules are evaluated quickly and cached to keep checkout smooth.

**Q: Can I migrate existing WooCommerce shipping methods?**  
You can recreate your existing methods as RuleHook rules, then extend them with advanced conditions.

---

## 🖼️ Screenshots

1. RuleHook dashboard with rule editor.
2. Example of dynamic shipping rates at checkout.

---

## 🧾 Changelog

### 1.0.0
- Initial release.

---

## ⬆️ Upgrade Notice

### 1.0.0
First public release of RuleHook Connector.
