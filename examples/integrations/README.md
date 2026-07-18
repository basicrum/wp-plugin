# Consent Tool Integration Examples

These examples document the copy-and-paste adapters shipped with the installable
Basicrum plugin. Select **Follow external consent tool** in Basicrum, then use
the matching tab under **Basicrum > Visitor Privacy > Consent Tool
Integration** to copy the packaged adapter.

Load the Basicrum consent loader before the selected adapter. Load the adapter
as unblocked site code on every frontend page and call only one adapter. The
external tool remains the source of truth; Basicrum does not store a separate
consent decision.

## Borlabs Cookie 3.0.6+

The [`borlabs-cookie-v3.js`](../../plugins/basicrum/assets/js/integrations/borlabs-cookie-v3.js) adapter follows the public
Borlabs Cookie JavaScript contract:

- It reads `BorlabsCookie.Consents.hasConsent('basicrum')`.
- It synchronizes after `borlabs-cookie-after-init`.
- It synchronizes again after `borlabs-cookie-consent-saved`.
- It calls exactly one of Basicrum's opt-in or opt-out callbacks.

Use Borlabs Cookie 3.0.6 or newer. Version 3.0.6 introduced the
`borlabs-cookie-after-init` event required for reliable initialization when the
adapter loads before Borlabs finishes starting.

In Borlabs Cookie, create and enable a custom service with the service ID
`basicrum`, normally in the Statistics service group. Do not place the adapter
itself behind that service because it must also report rejection and withdrawal.

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
There is no separate Basicrum service to configure in Complianz. Complianz maps
its Statistics decision to WP Consent API's `statistics` category.

CookieYes 3.5.3 can also bridge its Analytics decision to WP Consent
API when that API is active, including in the standalone WordPress plugin. This
is the preferred CookieYes path because it uses the shared WordPress contract.
Do not load the direct CookieYes fallback below at the same time.

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
connected to the CookieYes web app and WP Consent API is not being used. The
direct adapter follows only CookieYes's Analytics category; Performance or any
other category cannot enable Basicrum.

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
