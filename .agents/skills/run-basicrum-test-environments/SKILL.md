---
name: run-basicrum-test-environments
description: Run and diagnose Basicrum's local WordPress and WooCommerce environments. Use for localhost provisioning, frontend loader inspection, HTTP beacon testing, seeded store pages, Docker ports, credentials, stale plugin copies, or test data.
---

# Run Basicrum Test Environments

Choose the environment that matches the question before changing code.

## Local WordPress on port 9080

Start and provision WordPress with Basicrum activated:

```bash
make up
```

Open `http://localhost:9080/wp-admin/` and use `admin` /
`basicrum-dev-password` unless `.env` overrides the defaults. `make down` stops
containers while preserving data; `make clean` resets the stack.

For missing frontend instrumentation, verify in this order:

1. Basicrum is active and monitoring is enabled.
2. Beacon URL and Brum Site ID are valid and saved.
3. The current page is eligible and the loader appears in raw HTML.
4. Immediate mode loads directly; consent-controlled mode waits for the external
   adapter to call one wrapper callback.
5. HTTP Strictness is enabled only when a local HTTP beacon is intentional.
6. Browser cache, page cache, and optimization plugins are not serving stale
   markup or changing script order.

Inspect generated HTML and browser network requests before assuming the plugin
failed. Do not weaken production URL security to solve a local setup issue.

## Inspectable WooCommerce on port 9081

```bash
make woocommerce-e2e-up
```

Open `http://localhost:9081/` and use `admin` /
`basicrum-e2e-password`. This isolated stack seeds the shop, product, cart,
checkout, order-pay, and order-received routes. It keeps its own plugin copy and
data, so reset it after source changes:

```bash
make woocommerce-e2e-down
make woocommerce-e2e-up
```

Use `WOOCOMMERCE_E2E_PORT` only when the default host port conflicts. Confirm
anonymous storefront access in a private session; an admin session can bypass
visibility restrictions and hide a setup defect.

Run the non-interactive browser suite with `make woocommerce-e2e`. Stop and reset
the inspectable stack with `make woocommerce-e2e-down` when finished.

## Diagnose before editing

- Read container logs and the current page response.
- Confirm which stack and port are running.
- Distinguish a stale isolated copy from current workspace source.
- Reproduce with the documented defaults before changing setup scripts.
- If setup changes, keep it idempotent and verify both a fresh volume and an
  already-provisioned volume.
