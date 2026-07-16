=== Basicrum - Real User Monitoring ===
Contributors: tstoychev
Tags: analytics, performance, rum, real-user-monitoring, web-vitals
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Budget, open source, Real User Monitoring powered by Boomerang.js. Track page load performance, page types, and Web Vitals.

== Description ==

Basicrum is a free, open source Real User Monitoring (RUM) system. This plugin integrates [Boomerang.js](https://github.com/akamai/boomerang) into your WordPress site to collect real performance data from your visitors.

**Features:**

* **Real User Monitoring** — Collect page load timing, resource timing, and continuity metrics from actual visitors.
* **Page Type Detection** — Automatically tags beacons with the WordPress page type (home, post, page, category, archive, search, 404) and WooCommerce types (product, cart, checkout).
* **Site ID** — UUID v4 identifier to distinguish multiple sites reporting to the same beacon endpoint.
* **GDPR Consent Mode** — Conditional loading with JavaScript API (`OPT_IN_BASIC_RUM()` / `OPT_OUT_BASIC_RUM()`) for consent banner integration.
* **3-Tier Script Loading** — Preload → iframe → direct script loading strategy for optimal performance.
* **Configurable Beacon Delay** — Wait after onload before sending the beacon for more complete data collection.
* **Cache Plugin Compatibility** — Automatically excluded from optimization by WP Rocket, Autoptimize, LiteSpeed Cache, SG Optimizer, W3 Total Cache, and WP Optimize.

**How it works:**

1. Configure your beacon endpoint URL (or use the default Basicrum collector).
2. Enable monitoring and optionally set a Site ID.
3. The plugin injects Boomerang.js on your frontend pages.
4. Performance beacons are sent to your collector for analysis.

Visit [basicrum.com](https://www.basicrum.com/) for more information about the Basicrum platform.

== Installation ==

1. Upload the `basicrum` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Basicrum** in the admin sidebar to configure settings.
4. Set your beacon URL and Site ID.
5. Enable monitoring.

== Frequently Asked Questions ==

= What is Real User Monitoring? =

Real User Monitoring (RUM) collects performance data from actual visitors to your site, as opposed to synthetic testing which simulates visits. This gives you accurate data about how fast your site loads for real people.

= What is Boomerang.js? =

Boomerang.js is an open source JavaScript library by Akamai that measures the performance of your website from the end user's perspective. It collects detailed timing data and sends it to a beacon endpoint for analysis.

= Do I need a Basicrum account? =

You can use the default beacon endpoint or point to your own self-hosted Basicrum collector. Visit [basicrum.com](https://www.basicrum.com/) for hosted options.

= Is this GDPR compliant? =

Yes. Enable Consent Mode in the settings to require explicit user consent before any monitoring data is collected. Use the JavaScript API to integrate with your consent banner.

= Does it work with WooCommerce? =

Yes. When WooCommerce is active, the plugin automatically detects shop, product, cart, checkout, and order confirmation page types.

== Screenshots ==

1. Admin settings page — General settings with beacon URL and Site ID.
2. Privacy settings — Consent mode configuration with JS API documentation.
3. Developer settings — Script position and debug options.

== Changelog ==

= 1.0.1 =
* Complete rewrite with OOP architecture and PSR-4 autoloading.
* Added page type detection (WordPress + WooCommerce).
* Added Site ID (UUID v4) support.
* Added GDPR consent mode with JavaScript API.
* Added 3-tier script loading strategy (preload → iframe → direct).
* Upgraded Boomerang.js to v1.815.60.
* Added compatibility with popular caching plugins.
* Added version-based upgrade migrations.
* Added proper input validation and sanitization.
* Added unit and integration tests.
* Follows WordPress coding standards (PHPCS).

= 1.0.0 =
* Initial proof-of-concept release.
* Basic Boomerang.js injection with configurable beacon URL and delay.

== Upgrade Notice ==

= 1.0.1 =
Major rewrite. Your existing settings will be automatically migrated. Please verify your configuration after upgrading.
