=== Basicrum - Real User Monitoring ===
Contributors: tstoychev
Tags: analytics, performance, rum, real-user-monitoring, web-vitals
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Open source Real User Monitoring powered by Boomerang.js. Track page load performance, page types, and Web Vitals.

== Description ==

Basicrum is a free, open source Real User Monitoring (RUM) system. This plugin integrates [Boomerang.js](https://github.com/akamai/boomerang) into your WordPress site to collect real performance data from your visitors.

**Features:**

* **Real User Monitoring** - Collect page load timing, resource timing, and continuity metrics from actual visitors.
* **Page Type Detection** - Automatically tags beacons with the WordPress page type (home, post, page, category, archive, search, 404) and WooCommerce types (product, cart, checkout).
* **Brum Site ID** - Connect this WordPress site to the matching site in your Basicrum backoffice.
* **Consent-Controlled Loading** - Wait for an external consent tool before loading Boomerang, with `OPT_IN_BASICRUM_LOADER_WRAPPER()` and `OPT_OUT_BASICRUM_LOADER_WRAPPER()` integration callbacks.
* **3-Tier Script Loading** - Preload, iframe, and direct script loading strategy for optimal performance.
* **Configurable Beacon Delay** - Wait after onload before sending the beacon for more complete data collection.
* **Cache Plugin Compatibility** - Automatically excluded from optimization by WP Rocket, Autoptimize, LiteSpeed Cache, SG Optimizer, W3 Total Cache, and WP Optimize.

**How it works:**

1. Configure the beacon endpoint URL for your hosted or self-hosted Basicrum collector.
2. Enable monitoring and copy the Brum Site ID from your Basicrum backoffice.
3. The plugin injects Boomerang.js on your frontend pages.
4. Performance beacons are sent to your collector for analysis.

Visit [basicrum.com](https://www.basicrum.com/) for more information about the Basicrum platform.

== Installation ==

1. Upload the `basicrum` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **Basicrum** in the admin sidebar to configure settings.
4. Set your beacon URL and Brum Site ID.
5. Enable monitoring.

== Frequently Asked Questions ==

= What is Real User Monitoring? =

Real User Monitoring (RUM) collects performance data from actual visitors to your site, as opposed to synthetic testing which simulates visits. This gives you accurate data about how fast your site loads for real people.

= What is Boomerang.js? =

Boomerang.js is an open source JavaScript library by Akamai that measures the performance of your website from the end user's perspective. It collects detailed timing data and sends it to a beacon endpoint for analysis.

= Do I need a Basicrum account? =

Yes. You need a Basicrum collector endpoint and the matching Brum Site ID. Visit [basicrum.com](https://www.basicrum.com/) for hosted options, or use your own self-hosted collector.

= Does Basicrum make my site compliant with privacy laws? =

No plugin can guarantee legal compliance for a site. Basicrum provides immediate and consent-controlled loading, but it does not determine your legal basis, display a consent popup, retain a server-side consent record, or configure your privacy notice. Choose the loading behavior that matches your consent tool and the requirements that apply to your site.

= How do I connect my consent or cookie tool? =

Select **Wait for visitor consent** under **Basicrum > Visitor Privacy**. Load the consent integration after the configured Script Position, then call `window.OPT_IN_BASICRUM_LOADER_WRAPPER()` or `window.OPT_OUT_BASICRUM_LOADER_WRAPPER()` on every page. Basicrum does not persist a separate consent choice across page loads. If consent is withdrawn after Boomerang loading starts, reload the page before granting it again.

= Does it work with WooCommerce? =

Yes. When WooCommerce is active, the plugin automatically detects shop, product, cart, checkout, and order confirmation page types.

= Can I use an HTTP beacon URL during local development? =

Yes. Enable HTTP Strictness under Basicrum's Developer Settings to preserve HTTP beacon URLs. Keep it disabled on production sites so HTTP beacon URLs are automatically upgraded to HTTPS.

== Screenshots ==

1. Admin settings page - General settings with beacon URL and Brum Site ID.
2. Visitor privacy settings - Immediate or consent-controlled loading with JavaScript API documentation.
3. Performance settings - Beacon timing and script position options.
4. Developer settings - HTTP strictness and loader debugging options.

== Changelog ==

= 1.0.2 =
* Replaced the unused consent-mode selector with clear immediate and consent-controlled loading choices.
* Made external consent tools authoritative on every page through two explicit callbacks.
* Added a privacy-safe default, transparent integration guidance, and WordPress Privacy Policy Guide content.

= 1.0.1 =
* Complete rewrite with OOP architecture and PSR-4 autoloading.
* Added page type detection (WordPress + WooCommerce).
* Added Brum Site ID support.
* Added consent-controlled loading with JavaScript API.
* Added 3-tier preload, iframe, and direct script loading strategy.
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

= 1.0.2 =
Privacy controls now describe their real runtime behavior. Existing sites keep their immediate or consent-controlled loader choice, but consent integrations must call the matching callback on every page after the Basicrum loader is available. New installations wait for an opt-in callback by default.

= 1.0.1 =
Major rewrite. Your existing settings will be automatically migrated. Please verify your configuration after upgrading.
