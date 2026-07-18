---
name: extend-basicrum-page-types
description: Add and test Basicrum p_type detection for WordPress and WooCommerce. Use for PageTypeDetector.php or shop, product, cart, checkout, order-pay, order-received, account, archive, and other page classifications.
---

# Extend Basicrum Page Types

Treat `p_type` as a public analytics taxonomy whose names and precedence must be
stable, observable in real beacons, and safe when optional plugins are absent.

## Define the classification

1. Inspect `plugins/basicrum/src/PageTypeDetector.php` and its unit tests.
2. Confirm the platform's official conditional functions and the lifecycle point
   at which the main query makes them reliable.
3. Choose a stable `p_type` label and place the condition in specificity order.
   WooCommerce order-received and order-pay checks must precede general checkout;
   product checks must precede general WordPress single-page checks.
4. Guard optional plugin classes and functions so Basicrum works without
   WooCommerce.
5. Preserve the `basicrum_page_type` filter and the `unknown` fallback.

Use external projects only to inform taxonomy ideas. Implement the Basicrum
logic independently and acknowledge inspiration accurately when adding a new
external source.

## Cover three layers

- Add unit coverage for the new condition, false path, overlapping-condition
  precedence, optional-plugin absence, and filter behavior when applicable.
- Confirm `Assets` serializes the value into inline configuration.
- For commerce routes, extend the isolated WooCommerce browser suite so it
  visits the real page anonymously and asserts the actual emitted beacon.

The E2E assertion must verify `p_type`, `p_gen=wp`, and `brum_site_id`, not only
DOM markup. Seed the minimum product, cart, or order state needed for the route.
Keep Coming soon mode disabled so anonymous storefront visibility is part of the
test.

## Verify

```bash
make lint-php
make lint
make analyse
make unit
make woocommerce-e2e
make conventions
git diff --check
```

When changing the WooCommerce environment, keep its version and checksum paired
in `tools/setup-woocommerce-e2e.sh` and keep Docker tags paired with immutable
digests in `docker/woocommerce-e2e.yml`.
