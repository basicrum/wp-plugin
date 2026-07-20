=== Basicrum - Real User Monitoring ===
Contributors: basicrum
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

You need a Basicrum collector endpoint and the matching Brum Site ID. A hosted account from [basicrum.com](https://www.basicrum.com/) is one way to get them; running your own self-hosted collector requires no account.

= Does Basicrum make my site compliant with privacy laws? =

No plugin can guarantee legal compliance for a site. Basicrum provides immediate and consent-controlled loading, but it does not determine your legal basis, display a consent popup, retain a server-side consent record, or configure your privacy notice. Choose the loading behavior that matches your consent tool and the requirements that apply to your site.

= How do I connect my consent or cookie tool? =

Under **Basicrum > Visitor Privacy > Visitor Consent**, select **Monitor without consent** only when your site is permitted to monitor without a prior consent check. Select **Require consent before monitoring (recommended)** to keep Boomerang off and send no data until your consent or cookie tool reports an allow decision on each page.

When consent is required, **Consent Tool Connection** appears and defaults to **Automatic connection**. Basicrum gives WP Consent API priority. Without it, Basicrum loads the Borlabs Cookie adapter or CookieYes adapter only when exactly one corresponding plugin marker is detected. CookieYes legacy mode is not selected because it exposes an incompatible browser API. The CookieYes adapter still fails closed unless the connected browser API is available and reports an Analytics decision. If none or multiple direct providers are detected, monitoring remains blocked rather than guessing. Automatic mode shows an Active, Action needed, Off, or Blocked verdict; evidence for each supported provider; one next action; and copyable diagnostics that exclude the Beacon URL and Brum Site ID.

Select **Manual callbacks** to reveal the detailed setup and copy-ready tabs for Borlabs Cookie v3, WP Consent API with Complianz or CookieYes, connected CookieYes, and generic callbacks. The manual Borlabs adapter supports 3.0.6+, while automatic detection requires 3.2+. Complianz requires the standalone WP Consent API plugin. Load one selected integration after the configured Script Position. It must call `window.OPT_IN_BASICRUM_LOADER_WRAPPER()` when monitoring is allowed or `window.OPT_OUT_BASICRUM_LOADER_WRAPPER()` when monitoring is denied, expires, or is withdrawn. Call one callback on every page. Basicrum does not persist a separate consent choice across page loads. A region-aware tool may report allowed before visitor interaction in an opt-out region. If consent is withdrawn after Boomerang loading starts, reload the page before granting it again.

Detection confirms that a supported integration is present, not that its consent categories are configured correctly. Follow the status panel's verification step and test both allow and deny in a private window.

= Does Basicrum send URL query strings? =

By default, Basicrum may send complete query strings in page, navigation, referrer, and resource URLs. Enable **Basicrum > Visitor Privacy > Strip Query Strings** to replace them with `?qs-redacted` before sending beacons. URL paths are still collected. Review whether your URLs can contain personal or sensitive information before deciding whether to enable this setting.

= Does Basicrum set cookies? =

When Boomerang runs, it sets a first-party `RT` cookie that maintains timing state across consecutive pages. It uses `SameSite=Strict`, carries the `Secure` flag on HTTPS sites, and expires seven days after the last monitored page view. In **Require consent before monitoring** mode no cookie is set until your consent tool reports an allow decision, and the opt-out callback removes the `RT` cookie together with any legacy `BA` cookie left by older Boomerang setups.

= Does it work with WooCommerce? =

Yes. When WooCommerce is active, the plugin emits `shop`, `product`, `product_category`, `cart`, `checkout`, `checkout_payment`, `checkout_success`, and `account` page types when the corresponding WooCommerce conditional is true.

= Can I use an HTTP beacon URL during local development? =

Yes. Enable HTTP Strictness under Basicrum's Developer Settings to preserve HTTP beacon URLs. Keep it disabled on production sites so HTTP beacon URLs are automatically upgraded to HTTPS.

== External services ==

This plugin sends visitor performance beacons to the collector endpoint that the site administrator configures under **Basicrum > General Settings > Beacon URL**. No beacon is sent until monitoring is enabled and, in the default **Require consent before monitoring** mode, an allow decision is reported by the site's consent tool.

Each beacon carries performance data: page and resource URLs (with optional query-string redaction), timing metrics, the detected page type, and the configured Brum Site ID. As with any HTTP request, the collector also observes the visitor's IP address and user agent. Beacons go only to the configured Beacon URL; the plugin makes no requests to basicrum.com or any other service on its own.

The collector can be the hosted Basicrum service or a self-hosted installation. For the hosted service, see the [Basicrum website](https://www.basicrum.com/) for service and privacy information. Operators of self-hosted collectors are responsible for their own hosting arrangements.

== Third-party software ==

Basicrum-owned code is licensed under the MIT License. The bundled Boomerang 1.815.60 library retains its upstream BSD license and copyright notices. See `THIRD-PARTY-NOTICES.txt` and `assets/js/boomr/LICENSE.txt` in the plugin package.

The bundled file `assets/js/boomr/boomerang-1.815.60.cutting-edge.min.js` is a minified build of the open source Boomerang project by Akamai ([github.com/akamai/boomerang](https://github.com/akamai/boomerang)). It is byte-for-byte reproducible from public source: commit `ead2783a33a2ce91205fe34f8fc992433faba9a2` on the `master` branch of [github.com/basicrum/boomerang](https://github.com/basicrum/boomerang), built with Node 12 and the repository's Grunt tooling (`grunt clean build --build-flavor=cutting-edge --build-number=815`). The version banner inside the file stamps the parent commit `564759ed70de7801bb64de5e2025fb6ac049ff5f` because the final source change was uncommitted when the shipped file was generated; the code content matches `ead2783a` exactly. The Basicrum fork differs from upstream Boomerang by a small set of maintained commits that remove Long Tasks monitoring, remove the deprecated FID metric and rework Time to First Interaction, drop unused utility functions, and add the Basicrum configuration bootstrap.

== Changelog ==

= 0.0.8 =
* First public release: Real User Monitoring via the bundled Boomerang.js with WordPress and WooCommerce page-type detection, a consent-required default with fail-closed automatic consent-tool detection (WP Consent API, Borlabs Cookie 3.2+, CookieYes 3.x) plus manual callbacks, optional query-string redaction, editable WordPress Privacy Policy Guide text, and optimization-plugin script exclusions.
