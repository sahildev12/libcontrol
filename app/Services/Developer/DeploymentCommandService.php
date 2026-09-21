<?php

namespace App\Services\Developer;

use App\Models\DeploymentCommand;
use App\Models\LicensedDeployment;
use Illuminate\Support\Str;

class DeploymentCommandService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function queue(LicensedDeployment $deployment, string $action, array $payload = []): DeploymentCommand
    {
        return DeploymentCommand::query()->create([
            'licensed_deployment_id' => $deployment->id,
            'action' => $action,
            'payload' => $payload ?: null,
            'status' => DeploymentCommand::STATUS_PENDING,
            'idempotency_key' => Str::lower(Str::random(32)),
        ]);
    }

    /**
     * @return list<array{id: string, action: string, payload: array<string, mixed>|null}>
     */
    public function pendingForDeployment(LicensedDeployment $deployment, int $limit = 10): array
    {
        $commands = DeploymentCommand::query()
            ->where('licensed_deployment_id', $deployment->id)
            ->where('status', DeploymentCommand::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        foreach ($commands as $command) {
            $command->update([
                'status' => DeploymentCommand::STATUS_SENT,
                'sent_at' => now(),
            ]);
        }

        return $commands->map(fn (DeploymentCommand $command) => [
            'id' => (string) $command->id,
            'action' => $command->action,
            'payload' => $command->payload,
        ])->all();
    }

    /**
     * @param  list<array{id: string, status: string, result?: string}>  $results
     */
    public function recordResults(array $results): void
    {
        foreach ($results as $result) {
            $command = DeploymentCommand::query()->find($result['id'] ?? null);

            if (! $command) {
                continue;
            }

            $status = ($result['status'] ?? '') === 'completed'
                ? DeploymentCommand::STATUS_COMPLETED
                : DeploymentCommand::STATUS_FAILED;

            $command->update([
                'status' => $status,
                'result' => $result['result'] ?? null,
                'completed_at' => now(),
            ]);
        }
    }
}
