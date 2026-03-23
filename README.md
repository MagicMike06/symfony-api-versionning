# API Versioning Bundle

A Symfony bundle for **incremental API versioning**. Each version class describes only the **changes introduced** by that version. The bundle automatically intercepts requests and responses, applying transformations in a chain — allowing a client on `v1.0.0` to communicate with a server on `v3.0.0` with zero changes on the client side.

---

## How it works

```
Client (v1.0.0) → [apply v2.0.0 changes] → [apply v3.0.0 changes] → Server handler
Client (v1.0.0) ← [revert v3.0.0 changes] ← [revert v2.0.0 changes] ← Server response
```

When a request comes in with header `X-API-Version: 1.0.0`, the bundle:
1. **On request** — applies each version `> 1.0.0` in ascending order (`onRequest`)
2. **On response** — reverts each version `> 1.0.0` in descending order (`onResponse`)

---

## Installation

```bash
composer require api-versionning/symfony-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    ApiVersioning\ApiVersioningBundle::class => ['all' => true],
];
```

---

## Configuration

```yaml
# config/packages/api_versioning.yaml
api_versioning:
  enabled: true               # default: true
  header_name: X-API-Version  # default: "X-API-Version"
  versions: []                # list of FQCNs; empty = use autowiring (recommended)
```

### Using explicit version list (optional)

If you prefer to declare versions explicitly rather than relying on autoconfiguration:

```yaml
api_versioning:
  versions:
    - App\ApiVersion\V200
    - App\ApiVersion\V300
```

---

## Creating a version

### Using the Maker (recommended)

If `symfony/maker-bundle` is installed, generate a skeleton class with:

```bash
php bin/console make:api-version 2.0.0
```

This creates `src/ApiVersion/V200.php` pre-filled with the correct namespace, interface, and method stubs.

### Manually

Implement `ApiVersionInterface`:

```php
namespace App\ApiVersion;

use ApiVersioning\Contract\ApiVersionInterface;
use ApiVersioning\Context\RouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class V200 implements ApiVersionInterface
{
    public function getName(): string
    {
        return '2.0.0';
    }

    public function getDescription(): string
    {
        return 'Renamed "username" field to "name" in user responses.';
    }

    public function onRequest(RouteContext $context, Request $request): void
    {
        // Upgrade incoming request from v1 format to v2 format
        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['username'])) {
            $data['name'] = $data['username'];
            unset($data['username']);
            // update request content...
        }
    }

    public function onResponse(RouteContext $context, Response $response): void
    {
        // Downgrade outgoing response from v2 format back to v1 format
        $data = json_decode($response->getContent(), true) ?? [];
        if (isset($data['name'])) {
            $data['username'] = $data['name'];
            unset($data['name']);
            $response->setContent(json_encode($data));
        }
    }
}
```

With autoconfiguration enabled (the default), Symfony detects any class implementing `ApiVersionInterface` and registers it automatically — no service declaration needed.

---

## Listening to version events

The bundle dispatches events around each version transformation, allowing fine-grained observability or override logic.

| Event constant | Dispatched |
|---|---|
| `ApiVersionEvents::BEFORE_VERSION_REQUEST` | Before `onRequest()` is called |
| `ApiVersionEvents::AFTER_VERSION_REQUEST` | After `onRequest()` is called |
| `ApiVersionEvents::BEFORE_VERSION_RESPONSE` | Before `onResponse()` is called |
| `ApiVersionEvents::AFTER_VERSION_RESPONSE` | After `onResponse()` is called |

All events extend `AbstractApiVersionEvent` and carry `versionName`, `versionDescription`, `routeContext`, and the `Request` or `Response` object.

```php
use ApiVersioning\Event\ApiVersionEvents;
use ApiVersioning\Event\BeforeVersionRequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ApiVersionEvents::BEFORE_VERSION_REQUEST)]
class MyVersionListener
{
    public function __invoke(BeforeVersionRequestEvent $event): void
    {
        // $event->versionName, $event->request, $event->routeContext
    }
}
```

---

## Architecture

```
src/
├── ApiVersioningBundle.php
├── Contract/
│   ├── ApiVersionInterface.php          ← implement this in your app
│   ├── ApiVersionProviderInterface.php
│   └── ApiVersionResolverInterface.php
├── Context/
│   └── RouteContext.php                 ← readonly, carries route name
├── Event/
│   ├── ApiVersionEvents.php             ← event name constants
│   ├── AbstractApiVersionEvent.php
│   ├── BeforeVersionRequestEvent.php
│   ├── AfterVersionRequestEvent.php
│   ├── BeforeVersionResponseEvent.php
│   └── AfterVersionResponseEvent.php
├── Provider/
│   └── DefaultApiVersionProvider.php   ← sorts versions via version_compare()
├── Resolver/
│   └── DefaultApiVersionResolver.php   ← reads X-API-Version header
├── Manager/
│   └── ApiVersionEventManager.php      ← core chaining logic
├── EventListener/
│   └── ApiVersioningKernelListener.php ← hooks kernel.request / kernel.response
├── Compiler/
│   └── ApiVersioningCompilerPass.php
└── DependencyInjection/
    ├── ApiVersioningExtension.php
    └── Configuration.php
```

### Replacing the resolver

To read the version from somewhere other than a header (JWT claim, query param, etc.), implement `ApiVersionResolverInterface` and alias it:

```php
// config/services.yaml
ApiVersioning\Contract\ApiVersionResolverInterface:
    alias: App\ApiVersion\MyCustomResolver
```

### Replacing the provider

To source version objects from a database or remote config, implement `ApiVersionProviderInterface` and alias it the same way.

---

## Requirements

- PHP ≥ 8.4
- Symfony ≥ 7.4

---

## Running the tests

```bash
composer install
vendor/bin/phpunit
```
