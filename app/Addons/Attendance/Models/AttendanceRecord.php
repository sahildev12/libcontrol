<?php

namespace App\Addons\Attendance\Models;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const METHOD_STUDENT_QR = 'student_qr';

    public const METHOD_STAFF_GPS = 'staff_gps';

    public const METHOD_MANUAL = 'manual';

    public const METHOD_BIOMETRIC = 'biometric';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'student_id',
        'attendance_date',
        'check_in_at',
        'method',
        'marked_by_user_id',
        'latitude',
        'longitude',
        'device_meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'device_meta' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }
}
