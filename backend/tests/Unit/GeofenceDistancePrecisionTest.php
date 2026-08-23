<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Services\GeofenceService;
use Tests\TestCase;

class GeofenceDistancePrecisionTest extends TestCase
{
    private GeofenceService $geofenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geofenceService = new GeofenceService();
    }

    public function test_exact_same_coordinates_returns_zero_meters(): void
    {
        $lat = -7.7700;
        $lng = 110.3700;

        $distance = $this->geofenceService->calculateDistanceMeters($lat, $lng, $lat, $lng);

        $this->assertEquals(0.0, $distance);
    }

    public function test_coordinates_within_geofence_radius_is_valid(): void
    {
        $branch = new Branch([
            'lat' => -7.770000,
            'lng' => 110.370000,
            'radius_meters' => 50,
        ]);

        // geser koordinat sedikit (sekitar 15-20 meter)
        $userLat = -7.770100;
        $userLng = 110.370100;

        $result = $this->geofenceService->validateBranchRadius($branch, $userLat, $userLng);

        $this->assertTrue($result['is_valid']);
        $this->assertLessThanOrEqual(50, $result['distance_meters']);
    }

    public function test_coordinates_outside_geofence_radius_is_invalid(): void
    {
        $branch = new Branch([
            'lat' => -7.770000,
            'lng' => 110.370000,
            'radius_meters' => 50,
        ]);

        // geser koordinat sekitar 200 meter
        $userLat = -7.772000;
        $userLng = 110.370000;

        $result = $this->geofenceService->validateBranchRadius($branch, $userLat, $userLng);

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThan(50, $result['distance_meters']);
    }

    public function test_long_distance_haversine_calculation(): void
    {
        // koordinat Tugu Jogja ke Titik Nol Km Malioboro (~1.8 km)
        $tuguLat = -7.782884;
        $tuguLng = 110.367064;

        $nolKmLat = -7.800263;
        $nolKmLng = 110.365942;

        $distance = $this->geofenceService->calculateDistanceMeters($tuguLat, $tuguLng, $nolKmLat, $nolKmLng);

        // jarak garis lurus sekitar 1.930 meter (+- 100m)
        $this->assertGreaterThan(1800, $distance);
        $this->assertLessThan(2100, $distance);
    }
}
