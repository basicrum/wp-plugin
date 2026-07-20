# Basicrum WordPress Plugin

Basicrum is a privacy-first Real User Monitoring integration for WordPress. It loads the bundled Boomerang library, tags WordPress and WooCommerce page types, and sends performance beacons to a configured hosted or self-hosted Basicrum collector.

The installable plugin lives in [`plugins/basicrum/`](plugins/basicrum/). Build release artifacts through the repository tooling instead of zipping the source directory directly.

## Privacy

- New installations require an external consent decision before monitoring by default.
- Immediate monitoring remains an explicit administrator choice.
- Query-string redaction is available but disabled by default.
- Basicrum contributes editable disclosure text to the WordPress Privacy Policy Guide.
- Basicrum does not display a consent popup, choose a legal basis, or make a site compliant by itself.

The complete webmaster-facing behavior and consent instructions live in the [WordPress plugin readme](plugins/basicrum/readme.txt). Privacy review evidence and unresolved decisions are tracked under [`docs/audits/`](docs/audits/).

## Repository layout

| Path | Purpose |
| --- | --- |
| [`plugins/basicrum/`](plugins/basicrum/) | Installable plugin source |
| [`tools/`](tools/) | Setup, test, and release scripts |
| [`docker/`](docker/) | Local WordPress and WooCommerce environments |
| [`tests/javascript/`](tests/javascript/) | Browser tests for loaders, consent adapters, and settings |
| [`examples/integrations/`](examples/integrations/) | Consent-tool integration examples |
| [`docs/audits/`](docs/audits/) | Privacy and operator-experience audit records |
| [`docs/acknowledgments.md`](docs/acknowledgments.md) | External ideas and references that informed the implementation |
| [`wordpress-org-assets/`](wordpress-org-assets/) | WordPress.org directory artwork |
| [`.agents/skills/`](.agents/skills/) | Repeatable Basicrum development workflows for coding agents |

## Quick start

Docker, Docker Compose, and Make are required. From the repository root:

```bash
cp .env.example .env
make up
```

WordPress is available at <http://localhost:9080/>. The local administrator credentials are `admin` / `basicrum-dev-password`. Use `make down` to stop the stack without deleting its data, or `make clean` to reset it.

Run `make help` for every available command. Common checks are:

```bash
make lint
make analyse
make unit
make js-test
make package-smoke
```

See [AGENTS.md](AGENTS.md) for repository invariants, the complete check suite, and the project-specific skills used to change privacy, settings, consent, page-type detection, CI, and releases.

## Releases

`make package` creates `release/basicrum.zip` and its SHA-256 checksum from a temporary production Composer install. `make package-smoke` installs that ZIP into clean WordPress and checks activation, the administration page, frontend loading, and PHP logs.

The plugin header version, `BASICRUM_VERSION`, WordPress `Stable tag`, top changelog version, and `v<version>` release tag must match.

## Contributing and security

Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request. Report suspected vulnerabilities privately according to [SECURITY.md](SECURITY.md), not through a public issue.

Basicrum-owned code is available under the [MIT License](LICENSE). Bundled third-party software retains its upstream license; see the plugin's [third-party notices](plugins/basicrum/THIRD-PARTY-NOTICES.txt).

## Contributors

Thank you to everyone who contributes to Basicrum. This grid is generated from GitHub's contributor data.

[![Basicrum contributors](https://contrib.rocks/image?repo=basicrum/basicrum-wordpress)](https://github.com/basicrum/basicrum-wordpress/graphs/contributors)
