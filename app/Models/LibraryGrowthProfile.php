<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryGrowthProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'gbp_claimed',
        'gbp_photos',
        'gbp_category',
        'gbp_hours',
        'google_maps_url',
        'google_review_count',
        'google_review_url',
        'facebook_url',
        'instagram_url',
        'last_whatsapp_campaign_at',
        'last_review_campaign_at',
        'referral_campaign_active',
        'referral_offer_text',
        'active_package',
        'package_active_until',
        'score_cached',
        'scored_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gbp_claimed' => 'boolean',
            'gbp_photos' => 'boolean',
            'gbp_category' => 'boolean',
            'gbp_hours' => 'boolean',
            'referral_campaign_active' => 'boolean',
            'last_whatsapp_campaign_at' => 'datetime',
            'last_review_campaign_at' => 'datetime',
            'package_active_until' => 'datetime',
            'scored_at' => 'datetime',
            'google_review_count' => 'integer',
            'score_cached' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
