<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\FacilityContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public static function log(string $action, Model $model, array $changes = []): void
    {
        $facilityId = $model->getAttribute('facility_id') ?? FacilityContext::id();

        AuditLog::query()->create([
            'facility_id' => $facilityId,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'changes' => self::sanitize($changes),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private static function sanitize(array $changes): array
    {
        $hidden = ['password', 'remember_token'];

        $walk = function (mixed $value) use (&$walk, $hidden): mixed {
            if (! is_array($value)) {
                return $value instanceof \BackedEnum ? $value->value : $value;
            }

            $clean = [];

            foreach ($value as $key => $item) {
                if (in_array($key, $hidden, true)) {
                    continue;
                }

                $clean[$key] = $walk($item);
            }

            return $clean;
        };

        return $walk($changes);
    }
}
