<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Event;

final class ApiVersionEvents
{
    public const BEFORE_VERSION_REQUEST  = 'api_versioning.before_version_request';
    public const AFTER_VERSION_REQUEST   = 'api_versioning.after_version_request';
    public const BEFORE_VERSION_RESPONSE = 'api_versioning.before_version_response';
    public const AFTER_VERSION_RESPONSE  = 'api_versioning.after_version_response';
}
