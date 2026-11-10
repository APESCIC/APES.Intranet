<?php

namespace Modules\Operations\Services;

use Carbon\CarbonImmutable;
use Modules\Operations\Models\Site;
use Throwable;

/**
 * Optional clock-in check that a device is near the site. Off unless the
 * DPIA approval date is recorded and the switch is on. Callers keep only the
 * pass or fail result, never the coordinates.
 */
final class Geofence
{
    public const PASS = 'pass';

    public const FAIL = 'fail';

    public function isEnabled(): bool
    {
        return (bool) config('operations.geofence.enabled') && $this->approvalDate() !== null;
    }

    public function appliesTo(?Site $site): bool
    {
        return $this->isEnabled() && $site !== null && $site->hasGeofenceArea();
    }

    public function approvalDate(): ?CarbonImmutable
    {
        $value = config('operations.geofence.dpia_approved_at');
        if (blank($value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return null;
        }

        return $date->isFuture() ? null : $date;
    }

    public function check(Site $site, ?float $latitude, ?float $longitude): string
    {
        if ($latitude === null || $longitude === null) {
            return self::FAIL;
        }

        $radius = $site->radius_metres ?: (int) config('operations.geofence.default_radius_metres');

        return $this->distanceMetres($site->latitude, $site->longitude, $latitude, $longitude) <= $radius
            ? self::PASS
            : self::FAIL;
    }

    private function distanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
