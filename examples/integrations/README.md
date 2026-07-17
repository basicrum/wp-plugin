# Consent Tool Integration Examples

These examples are copy-and-paste adapters for webmasters. They are development
and documentation files outside the installable Basicrum plugin.

## Borlabs Cookie v3

The [`borlabs-cookie-v3.js`](borlabs-cookie-v3.js) adapter follows the public
Borlabs Cookie v3 JavaScript contract:

- It reads `BorlabsCookie.Consents.hasConsent('basicrum')`.
- It synchronizes after `borlabs-cookie-after-init`.
- It synchronizes again after `borlabs-cookie-consent-saved`.
- It calls exactly one of Basicrum's opt-in or opt-out callbacks.

In Borlabs Cookie, create and enable a custom service with the service ID
`basicrum`, normally in the Statistics service group. Configure Basicrum to
**Wait for visitor consent**.

Load the Basicrum consent loader before this adapter. Then load the adapter as
unblocked site code on every frontend page. Do not place the adapter itself
behind the Basicrum service consent because it must also report rejection and
withdrawal. Borlabs remains the consent source of truth.

If consent is withdrawn after Boomerang has loaded, Basicrum disables
collection and removes its known measurement cookies. Reload the page before
granting consent again; an already disabled Boomerang instance is not restarted
on the same page.

The Playwright suite runs this exact adapter against
`tests/javascript/fixtures/borlabs-cookie-v3-test-double.js`. The test double
models the documented API and events; it is not the proprietary Borlabs Cookie
plugin.

Official reference:
[Borlabs Cookie JavaScript API v3](https://borlabs.io/kb/javascript-api-v3/).
