# Consent Tool Integration Examples

These examples document the adapters shipped with the installable Basicrum
plugin. Select **Require consent before monitoring** under **Basicrum > Visitor
Privacy > Visitor Consent**. Then choose **Automatic connection** to let Basicrum
detect and load one matching adapter, or **Manual callbacks** to use the matching
tab under **Consent Tool Connection** in the revealed **Manual Connection
Setup** panel.

Automatic handling gives WP Consent API priority. Without it, Basicrum selects
a direct Borlabs Cookie 3.2+ or connected modern CookieYes 3.x adapter only when
exactly one is detected. No provider or multiple direct providers leave the
consent loader blocked. Do not also paste an adapter manually while automatic
handling is active. The automatic status panel shows the selection evidence and
next action. Its copyable diagnostics omit the Beacon URL and Brum Site ID.
Detection does not prove that the consent popup publishes the required
decision, so test both allow and deny in a private window.

In manual mode, load the Basicrum consent loader before the selected adapter.
Load one adapter as unblocked site code on every frontend page. The external
tool remains the source of truth; Basicrum does not store a separate consent
decision.

Provider detection contracts were reviewed on 2026-07-19 against WP Consent API
2.0.1 and CookieYes 3.5.3 from WordPress.org, plus the Borlabs Cookie PHP API
and changelog documentation. Re-check these contracts when updating a packaged
adapter or the documented compatibility floor.

## Borlabs Cookie manual adapter: 3.0.6+; automatic detection: 3.2+

The [`borlabs-cookie-v3.js`](../../plugins/basicrum/assets/js/integrations/borlabs-cookie-v3.js) adapter follows the public
Borlabs Cookie JavaScript contract:

- It reads `BorlabsCookie.Consents.hasConsent('basicrum')`.
- It synchronizes after `borlabs-cookie-after-init`.
- It synchronizes again after `borlabs-cookie-consent-saved`.
- It calls exactly one of Basicrum's opt-in or opt-out callbacks.

Use Borlabs Cookie 3.0.6 or newer. Version 3.0.6 introduced the
`borlabs-cookie-after-init` event required for reliable initialization when the
adapter loads before Borlabs finishes starting.

Automatic detection requires Borlabs Cookie 3.2 or newer because Basicrum uses
the vendor-documented `borlabsCookieApi()` PHP marker introduced in 3.2. Sites
running 3.0.6 through 3.1.x can still use this adapter through Manual callbacks.
Basicrum does not inspect Borlabs internal classes as a substitute for its
public API.

In Borlabs Cookie, create and enable a custom service with the service ID
`basicrum`, normally in the Statistics service group. Do not place the adapter
itself behind that service because it must also report rejection and withdrawal.
Automatic detection does not create or configure this service.

JavaScript combination, delay, and minification can change consent-script order.
Exclude the Borlabs runtime, the Basicrum consent loader, and this adapter from
those optimizations. After changing a cache or optimization plugin, clear every
page/CDN cache and smoke-test a saved grant, a saved denial, a new grant, and a
withdrawal in a private browser session.

If consent is withdrawn after Boomerang has loaded, Basicrum disables collection
and removes its known measurement cookies. Reload the page before granting
consent again; an already disabled Boomerang instance is not restarted on the
same page.

The Playwright suite runs this exact adapter against
`tests/javascript/fixtures/borlabs-cookie-v3-test-double.js`. The test double
models the documented API and events; it is not the proprietary Borlabs Cookie
plugin.

References:

- [Borlabs Cookie JavaScript API v3](https://borlabs.io/kb/javascript-api-v3/)
- [Borlabs Cookie changelog](https://borlabs.io/borlabs-cookie/changelog/)
- [Community report about Borlabs and JavaScript aggregation](https://wordpress.org/support/topic/problems-with-new-borlabs-cookies/)

## WP Consent API with Complianz or CookieYes

The [`wp-consent-api.js`](../../plugins/basicrum/assets/js/integrations/wp-consent-api.js) adapter uses WordPress's shared
consent contract. Install and activate the separate WP Consent API plugin plus a
supported consent-management plugin. The adapter was reviewed against Complianz
7.5.0, CookieYes 3.5.3, and WP Consent API 2.0.1; both consent tools publish
their decisions through the shared API in those versions.

- It waits until a consent tool defines `window.wp_consent_type`.
- It reads `wp_has_consent('statistics')` for the current decision.
- It synchronizes after `wp_consent_type_defined`.
- It synchronizes after relevant `wp_listen_for_consent_change` events.
- It calls exactly one of Basicrum's opt-in or opt-out callbacks.

The initialization guard is intentional. WP Consent API allows tracking when no
consent type has been defined, so reading it before the consent tool initializes
could otherwise start Basicrum too early.

WP Consent API is region-aware. With no saved visitor choice, `statistics` is
denied in an opt-in region and allowed in an opt-out region. Therefore this
adapter follows the consent tool's current legal-policy decision; it does not
always wait for an accept-button click.

For Complianz, install WP Consent API and complete the normal Complianz wizard.
Complianz does not bundle WP Consent API, so the standalone plugin must be
active. There is no separate Basicrum service to configure in Complianz.
Complianz maps its Statistics decision to WP Consent API's `statistics`
category.

CookieYes 3.5.3 can also bridge its Analytics decision to WP Consent
API when that API is active, including in the standalone WordPress plugin. This
is the preferred CookieYes path because it uses the shared WordPress contract.
Automatic handling enforces this priority. Do not load the direct CookieYes
fallback below at the same time.

The Playwright suite runs this exact adapter against a WP Consent API test double
covering opt-in and opt-out region defaults, saved decisions, changes,
withdrawal, and duplicate events. It does not bundle Complianz, CookieYes, or WP
Consent API.

References:

- [Complianz and WP Consent API](https://complianz.io/wp-consent-api/)
- [WP Consent API](https://wordpress.org/plugins/wp-consent-api/)
- [CookieYes WordPress plugin](https://wordpress.org/plugins/cookie-law-info/)

## Connected CookieYes fallback

Use [`cookieyes.js`](../../plugins/basicrum/assets/js/integrations/cookieyes.js) only when the CookieYes WordPress plugin is
running its modern 3.x runtime, connected to the CookieYes web app, and WP
Consent API is not being used. Basicrum deliberately does not detect CookieYes
legacy mode: its `Cookie_Law_Info` runtime exposes an incompatible browser API.
The direct adapter follows only CookieYes's Analytics category; Performance or
any other category cannot enable Basicrum.

The adapter uses two forms of CookieYes state:

- The officially documented `cookieyes_consent_update` event, whose detail
  contains `accepted` and `rejected` category arrays.
- `getCkyConsent()` and `cookieyes_banner_load` for stored state on later page
  loads. CookieYes support and community integrations use these interfaces, but
  the current official events page documents only the update event.

CookieYes confirms that its direct browser events are unavailable to a
standalone WordPress installation that is not connected to the web app. Use the
WP Consent API adapter above for that installation. Keep this direct adapter
unblocked so it can report grants, denials, and withdrawals.

The Playwright test double deliberately models the two different payload shapes:
stored state contains a `categories` map, while consent updates contain only the
documented `accepted` and `rejected` arrays. The tests do not contact CookieYes
services.

References:

- [CookieYes browser events](https://www.cookieyes.com/documentation/events-on-cookie-banner-interactions/)
- [CookieYes connected-account limitation](https://wordpress.org/support/topic/cookieyes_consent_update-event-not-fire/)
- [Community example for update and stored-state events](https://wordpress.org/support/topic/run-js-after-accept/)
- [CookieYes support for `getCkyConsent()`](https://wordpress.org/support/topic/blocked-content/)
