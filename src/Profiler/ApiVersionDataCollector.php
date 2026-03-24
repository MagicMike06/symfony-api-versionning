<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Profiler;

use MagicMike\ApiVersioning\Contract\ApiVersionProviderInterface;
use MagicMike\ApiVersioning\Event\AbstractApiVersionEvent;
use MagicMike\ApiVersioning\Event\ApiVersionEvents;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionDataCollector extends AbstractDataCollector implements EventSubscriberInterface
{
    private array $pendingTimings = [];

    public function __construct(
        private readonly string                      $headerName,
        private readonly ApiVersionProviderInterface $provider,
    )
    {
        $this->reset();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ApiVersionEvents::BEFORE_VERSION_REQUEST => 'onBeforeTransformation',
            ApiVersionEvents::AFTER_VERSION_REQUEST => 'onAfterTransformation',
            ApiVersionEvents::BEFORE_VERSION_RESPONSE => 'onBeforeTransformation',
            ApiVersionEvents::AFTER_VERSION_RESPONSE => 'onAfterTransformation',
        ];
    }

    public function onBeforeTransformation(AbstractApiVersionEvent $event, string $eventName): void
    {
        $direction = \str_contains($eventName, 'request') ? 'request' : 'response';
        $key = $direction . ':' . $event->versionName;
        $this->pendingTimings[$key] = \microtime(true);
    }

    public function onAfterTransformation(AbstractApiVersionEvent $event, string $eventName): void
    {
        $direction = \str_contains($eventName, 'request') ? 'request' : 'response';
        $key = $direction . ':' . $event->versionName;
        $start = $this->pendingTimings[$key] ?? \microtime(true);
        $durationMs = (\microtime(true) - $start) * 1000;

        $this->data['transformations'][] = [
            'version' => $event->versionName,
            'description' => $event->versionDescription,
            'direction' => $direction,
            'duration_ms' => $durationMs,
        ];

        unset($this->pendingTimings[$key]);
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $resolvedVersion = $request->headers->get($this->headerName);
        $this->data['resolved_version'] = $resolvedVersion ?: null;
        $this->data['header_name'] = $this->headerName;
        $this->data['route'] = $request->attributes->get('_route', '');
        $this->data['was_versioned'] = $resolvedVersion !== null && $resolvedVersion !== '';

        $registeredVersions = [];
        foreach ($this->provider->getVersions() as $version) {
            $registeredVersions[] = [
                'name' => $version->getName(),
                'description' => $version->getDescription(),
            ];
        }
        $this->data['registered_versions'] = $registeredVersions;

        $total = 0.0;
        foreach ($this->data['transformations'] as $t) {
            $total += $t['duration_ms'];
        }
        $this->data['total_duration_ms'] = $total;
    }

    public function reset(): void
    {
        $this->data = [
            'resolved_version' => null,
            'header_name' => $this->headerName ?? 'X-API-Version',
            'route' => '',
            'was_versioned' => false,
            'registered_versions' => [],
            'transformations' => [],
            'total_duration_ms' => 0.0,
        ];
        $this->pendingTimings = [];
    }

    public static function getTemplate(): ?string
    {
        return '@ApiVersioning/data_collector.html.twig';
    }

    public function getResolvedVersion(): ?string
    {
        return $this->data['resolved_version'];
    }

    public function getHeaderName(): string
    {
        return $this->data['header_name'];
    }

    public function getRouteName(): string
    {
        return $this->data['route'];
    }

    public function wasVersioned(): bool
    {
        return $this->data['was_versioned'];
    }

    public function getRegisteredVersions(): array
    {
        return $this->data['registered_versions'];
    }

    public function getTransformations(): array
    {
        return $this->data['transformations'];
    }

    public function getTotalDurationMs(): float
    {
        return $this->data['total_duration_ms'];
    }

    public function getAppliedVersionNames(): array
    {
        $names = [];
        foreach ($this->data['transformations'] as $t) {
            $names[$t['version']] = true;
        }

        return \array_keys($names);
    }
}
