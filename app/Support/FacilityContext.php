<?php

namespace App\Support;

class FacilityContext
{
    private static ?int $facilityId = null;

    private static bool $hideTenantData = false;

    public static function set(?int $facilityId, bool $hideTenantData = false): void
    {
        self::$facilityId = $facilityId;
        self::$hideTenantData = $hideTenantData;
    }

    public static function id(): ?int
    {
        return self::$facilityId;
    }

    public static function shouldHideTenantData(): bool
    {
        return self::$hideTenantData;
    }

    public static function forget(): void
    {
        self::$facilityId = null;
        self::$hideTenantData = false;
    }
}
