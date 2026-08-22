<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;

class GeofenceService
{
    private const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * hitung jarak fisik dalam meter antara dua koordinat GPS menggunakan formula Haversine
     */
    public function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return round($angle * self::EARTH_RADIUS_METERS, 2);
    }

    /**
     * validasi apakah posisi pengguna berada di dalam batas radius cabang yang diizinkan
     *
     * @return array{is_valid: bool, distance_meters: float, allowed_radius_meters: int}
     */
    public function validateBranchRadius(Branch $branch, float $userLat, float $userLng): array
    {
        $distance = $this->calculateDistanceMeters(
            (float) $branch->lat,
            (float) $branch->lng,
            $userLat,
            $userLng
        );

        $allowedRadius = $branch->radius_meters;

        return [
            'is_valid' => $distance <= $allowedRadius,
            'distance_meters' => $distance,
            'allowed_radius_meters' => $allowedRadius,
        ];
    }
}
