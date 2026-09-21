<?php

namespace App\Services\Developer;

use App\Models\DeploymentCommand;
use App\Models\InstallationEvent;
use App\Models\LibraryRegistry;
use App\Models\LicensedDeployment;
use App\Services\Addons\AddonRegistry;
use App\Services\ActivityLogger;
use App\Models\User;
use Illuminate\Http\Request;

class DeploymentRemoteManageService
{
    public function __construct(
        private DeploymentCommandService $commands,
        private AddonRegistry $addonRegistry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function managePayload(LicensedDeployment $deployment): array
    {
        $registry = LibraryRegistry::query()
            ->whereIn('domain', $deployment->allowed_domains ?? [])
            ->orderByDesc('last_seen_at')
            ->first();

        $lastHeartbeat = InstallationEvent::query()
            ->where('license_key_hash', $deployment->license_key_hash)
            ->orderByDesc('last_seen_at')
            ->first();

        if (! $lastHeartbeat && ! empty($deployment->allowed_domains)) {
            $domains = collect($deployment->allowed_domains)
                ->map(fn (string $domain) => LicensedDeployment::normalizeDomain($domain))
                ->filter()
                ->values()
                ->all();

            if ($domains !== []) {
                $lastHeartbeat = InstallationEvent::query()
                    ->whereIn('domain', $domains)
                    ->orderByDesc('last_seen_at')
                    ->first();
            }
        }

        return [
            'deployment' => $deployment,
            'registry' => $registry,
            'lastHeartbeat' => $lastHeartbeat,
            'isOnline' => $lastHeartbeat?->last_seen_at?->gt(now()->subDay()) ?? false,
            'planTiers' => array_keys(config('libcontrol.plans', [])),
            'planSnapshot' => $this->planSnapshotForDeployment($deployment),
            'availableAddons' => $this->addonRegistry->catalogForSettings(),
            'commands' => DeploymentCommand::query()
                ->where('licensed_deployment_id', $deployment->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $planData
     */
    public function updatePlan(LicensedDeployment $deployment, array $planData, ?User $user, ?Request $request): LicensedDeployment
    {
        $deployment->update([
            'plan_tier' => $planData['plan_tier'],
            'max_seats_override' => $planData['max_seats_override'] ?? null,
            'max_halls_override' => $planData['max_halls_override'] ?? null,
            'max_branches_override' => $planData['max_branches_override'] ?? null,
        ]);

        $this->commands->queue($deployment, 'set_plan', [
            'plan_tier' => $deployment->plan_tier(),
            'max_seats_override' => $deployment->max_seats_override,
            'max_halls_override' => $deployment->max_halls_override,
            'max_branches_override' => $deployment->max_branches_override,
        ]);

        app(ActivityLogger::class)->record(
            $user,
            'deployment.plan_updated',
            "Queued plan update for {$deployment->client_name}.",
            $deployment,
            null,
            ['deployment_id' => $deployment->id],
            $request,
        );

        return $deployment->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function queueAction(LicensedDeployment $deployment, string $action, array $payload, ?User $user, ?Request $request): DeploymentCommand
    {
        $command = $this->commands->queue($deployment, $action, $payload);

        app(ActivityLogger::class)->record(
            $user,
            'deployment.command_queued',
            "Queued {$action} for {$deployment->client_name}.",
            $deployment,
            null,
            ['command_id' => $command->id, 'action' => $action],
            $request,
        );

        return $command;
    }

    /**
     * @return array<string, mixed>
     */
    private function planSnapshotForDeployment(LicensedDeployment $deployment): array
    {
        $tier = $deployment->planTier();
        $plans = config('libcontrol.plans', []);
        $defaults = $plans[$tier] ?? $plans['starter'] ?? [];

        return [
            'plan_tier' => $tier,
            'plan_label' => $defaults['label'] ?? ucfirst($tier),
            'max_seats' => $deployment->max_seats_override ?? $defaults['max_seats'] ?? null,
            'max_halls' => $deployment->max_halls_override ?? $defaults['max_halls'] ?? null,
            'max_branches' => $deployment->max_branches_override ?? $defaults['max_branches'] ?? null,
        ];
    }
}
