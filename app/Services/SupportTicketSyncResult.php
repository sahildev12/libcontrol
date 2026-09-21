<?php

namespace App\Services;

final class SupportTicketSyncResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $remoteId = null,
    ) {}

    public static function success(int $remoteId, string $message = 'Support ticket submitted. Our team will respond soon.'): self
    {
        return new self(true, $message, $remoteId);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
