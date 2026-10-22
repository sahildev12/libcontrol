<?php

namespace App\Services\EmailAutomation;

use App\Models\EmailAutomationLog;
use Illuminate\Database\QueryException;

class EmailAutomationLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        string $templateKey,
        string $recipientEmail,
        string $relatedType,
        int|string $relatedId,
        ?int $branchId = null,
        array $meta = [],
    ): bool {
        if (EmailAutomationLog::alreadySent($templateKey, $relatedType, $relatedId)) {
            return false;
        }

        try {
            EmailAutomationLog::query()->create([
                'template_key' => $templateKey,
                'recipient_email' => $recipientEmail,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'branch_id' => $branchId,
                'meta' => $meta ?: null,
                'sent_at' => now(),
            ]);
        } catch (QueryException) {
            return false;
        }

        return true;
    }

    public function wasSent(string $templateKey, string $relatedType, int|string $relatedId): bool
    {
        return EmailAutomationLog::alreadySent($templateKey, $relatedType, $relatedId);
    }
}
