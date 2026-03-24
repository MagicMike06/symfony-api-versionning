# CLAUDE.md — API Versioning Bundle

## Project overview

Symfony bundle for **incremental API versioning**. Each `ApiVersionInterface` class describes only the delta introduced by one version. The bundle chains transformations automatically on `kernel.request` and `kernel.response`.

Namespace root: `MagicMike\ApiVersioning\` → `src/`
Test namespace: `MagicMike\ApiVersioning\Tests\` → `tests/`

---

## Key architectural decisions

### Direction of transformations
- **`onRequest`** runs ascending (old → new): upgrades the client request to the current server format
- **`onResponse`** runs descending (new → old): downgrades the server response back to the client format

### Version filtering
Only versions **strictly greater than** the resolved client version are applied. Uses PHP's `version_compare()` — versions must be valid semver strings (`X.Y.Z`).

### Version resolution
Read from a configurable HTTP header (default `X-API-Version`). Returns `null` if the header is absent or empty → bundle does nothing (passthrough).

### Event system
Events fire **per version**, not globally. Order: `BEFORE → transform → AFTER` for each version in the chain.

### Listener priorities
- `kernel.request`: priority **-10** (after `RouterListener` at 32, so `_route` attribute is available)
- `kernel.response`: priority **0**

---

## Adding a new version

1. Run `php bin/console make:api-version X.Y.Z` (requires `symfony/maker-bundle`) — generates `src/ApiVersion/VXYZ.php`
2. Or create a class manually implementing `ApiVersionInterface`
3. With autoconfiguration enabled (default), Symfony picks it up automatically via the `api_versioning.version` tag
4. `getName()` must return a valid semver string (`X.Y.Z`)

## MakerBundle integration

- `src/Maker/MakeApiVersion.php` — the maker, tagged `maker.command`
- `src/Resources/skeleton/ApiVersion.tpl.php` — PHP include-style template (not Twig)
- `config/maker.yaml` — service definition, loaded only when `AbstractMaker::class` exists
- Class name derived from version: `2.0.0` → `V200` (dots removed, `V` prefix)
- Validation: semver regex `^\d+\.\d+\.\d+$` enforced in both `interact()` and `generate()`

---

## Extending the bundle

| Replace | Implement | Alias in services.yaml |
|---|---|---|
| Header resolver | `ApiVersionResolverInterface` | `MagicMike\ApiVersioning\Contract\ApiVersionResolverInterface` |
| Version provider | `ApiVersionProviderInterface` | `MagicMike\ApiVersioning\Contract\ApiVersionProviderInterface` |

---

## Running tests

```bash
composer install
vendor/bin/phpunit
```

14 tests, all unit. No Symfony kernel boot required.

---

## Code conventions

- `declare(strict_types=1)` on every PHP file
- `readonly` classes and constructor promotion where appropriate
- `final` on value objects (e.g. `RouteContext`)
- No mutable state in the manager — it reads from the provider each call
- Tests use anonymous classes to implement `ApiVersionInterface` inline (no fixtures directory)
