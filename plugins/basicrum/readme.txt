=== Basicrum - Real User Monitoring ===
Contributors: tstoychev
Tags: analytics, performance, rum, real-user-monitoring, web-vitals
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.0.8
License: MIT
License URI: https://opensource.org/licenses/MIT

Privacy-first Real User Monitoring with consent-controlled loading, page types, and Web Vitals.

== Description ==

Basicrum is a free, open source Real User Monitoring (RUM) system. This plugin integrates [Boomerang.js](https://github.com/akamai/boomerang) into your WordPress site to collect real performance data from your visitors.

Basicrum is privacy-first by design: new installations wait for an external consent decision by default, and the plugin contributes editable disclosure text to the WordPress Privacy Policy Guide. Site owners remain responsible for selecting an appropriate legal basis, configuring consent where required, and publishing an accurate privacy notice.

**Features:**

* **Real User Monitoring** - Collect page load timing, resource timing, and continuity metrics from actual visitors.
* **Page Type Detection** - Tags beacons with exact WordPress values such as `home`, `post`, `page`, `category_archive`, `archive`, `search`, and `404`, plus WooCommerce values including `shop`, `product`, `cart`, `checkout`, and `checkout_success`.
* **Brum Site ID** - Connect this WordPress site to the matching site in your Basicrum backoffice.
* **Consent-Controlled Loading** - Detects WP Consent API, Borlabs Cookie 3.2+, and the modern CookieYes plugin runtime. Every packaged adapter fails closed until its required consent API reports a decision. Manual `OPT_IN_BASICRUM_LOADER_WRAPPER()` and `OPT_OUT_BASICRUM_LOADER_WRAPPER()` callbacks support other tools.
* **Query String Protection** - Optionally replace URL query strings with a redaction marker before beacons are sent.
* **3-Tier Script Loading** - Uses non-blocking preload with iframe and direct script fallbacks.
* **Configurable Beacon Delay** - Wait after onload before sending the beacon for more complete data collection.
* **Optimization Plugin Exclusions** - Registers Basicrum script exclusions for selected JavaScript optimization features in WP Rocket, Autoptimize, LiteSpeed Cache, SG Optimizer, W3 Total Cache, and WP-Optimize.

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

Under **Basicrum > Visitor Privacy > Visitor Consent**, select **Monitor without consent** only when your site is permitted to monitor without a prior consent check. Select **Require consent before monitoring (recommended)** to keep Boomerang off and send no data until your consent or cookie tool reports an allow decision on each page.

When consent is required, **Consent Tool Connection** appears and defaults to **Automatic connection**. Basicrum gives WP Consent API priority. Without it, Basicrum loads the Borlabs Cookie adapter or CookieYes adapter only when exactly one corresponding plugin marker is detected. CookieYes legacy mode is not selected because it exposes an incompatible browser API. The CookieYes adapter still fails closed unless the connected browser API is available and reports an Analytics decision. If none or multiple direct providers are detected, monitoring remains blocked rather than guessing. Automatic mode shows an Active, Action needed, Off, or Blocked verdict; evidence for each supported provider; one next action; and copyable diagnostics that exclude the Beacon URL and Brum Site ID.

Select **Manual callbacks** to reveal the detailed setup and copy-ready tabs for Borlabs Cookie v3, WP Consent API with Complianz or CookieYes, connected CookieYes, and generic callbacks. The manual Borlabs adapter supports 3.0.6+, while automatic detection requires 3.2+. Complianz requires the standalone WP Consent API plugin. Load one selected integration after the configured Script Position. It must call `window.OPT_IN_BASICRUM_LOADER_WRAPPER()` when monitoring is allowed or `window.OPT_OUT_BASICRUM_LOADER_WRAPPER()` when monitoring is denied, expires, or is withdrawn. Call one callback on every page. Basicrum does not persist a separate consent choice across page loads. A region-aware tool may report allowed before visitor interaction in an opt-out region. If consent is withdrawn after Boomerang loading starts, reload the page before granting it again.

Detection confirms that a supported integration is present, not that its consent categories are configured correctly. Follow the status panel's verification step and test both allow and deny in a private window.

= Does Basicrum send URL query strings? =

By default, Basicrum may send complete query strings in page, navigation, referrer, and resource URLs. Enable **Basicrum > Visitor Privacy > Strip Query Strings** to replace them with `?qs-redacted` before sending beacons. URL paths are still collected. Review whether your URLs can contain personal or sensitive information before deciding whether to enable this setting.

= Does it work with WooCommerce? =

Yes. When WooCommerce is active, the plugin emits `shop`, `product`, `product_category`, `cart`, `checkout`, `checkout_payment`, `checkout_success`, and `account` page types when the corresponding WooCommerce conditional is true.

= Can I use an HTTP beacon URL during local development? =

Yes. Enable HTTP Strictness under Basicrum's Developer Settings to preserve HTTP beacon URLs. Keep it disabled on production sites so HTTP beacon URLs are automatically upgraded to HTTPS.

== Third-party software ==

Basicrum-owned code is licensed under the MIT License. The bundled Boomerang 1.815.60 library retains its upstream BSD license and copyright notices. See `THIRD-PARTY-NOTICES.md` and `assets/js/boomr/LICENSE.txt` in the plugin package.

== Changelog ==

= 0.0.8 =
* First public release.
* Reordered the Visitor Consent choices and hid Consent Tool Connection when consent is not required.
* Replaced the unused consent-mode selector with clear immediate and consent-controlled loading choices.
* Made external consent tools authoritative on every page through two explicit callbacks.
* Added a privacy-safe default, transparent integration guidance, and WordPress Privacy Policy Guide content.
* Added first-class query-string protection under Visitor Privacy, disabled by default.
* Added fail-closed automatic selection for WP Consent API, Borlabs Cookie 3.2+, and the modern CookieYes plugin marker, with manual callbacks available as an explicit choice.
