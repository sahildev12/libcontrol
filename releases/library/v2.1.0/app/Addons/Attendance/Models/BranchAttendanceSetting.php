<?php

namespace App\Addons\Attendance\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BranchAttendanceSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'student_qr_enabled',
        'staff_gps_enabled',
        'geofence_latitude',
        'geofence_longitude',
        'geofence_radius_meters',
        'qr_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_qr_enabled' => 'boolean',
            'staff_gps_enabled' => 'boolean',
            'geofence_latitude' => 'decimal:7',
            'geofence_longitude' => 'decimal:7',
            'geofence_radius_meters' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function forBranch(int $branchId): self
    {
        return static::query()->firstOrCreate(
            ['branch_id' => $branchId],
            [
                'student_qr_enabled' => true,
                'staff_gps_enabled' => true,
                'geofence_radius_meters' => 100,
                'qr_token' => Str::random(48),
            ],
        );
    }

    public function rotateQrToken(): self
    {
        $this->update(['qr_token' => Str::random(48)]);

        return $this->fresh();
    }

    public function checkInUrl(): string
    {
        return route('attendance.public.check-in', ['token' => $this->qr_token]);
    }
}
