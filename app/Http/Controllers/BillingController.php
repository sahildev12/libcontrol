<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\Payments\HubPaymentClient;
use App\Services\Payments\PaymentService;
use App\Services\PlanLimitService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(
        Request $request,
        HubPaymentClient $hub,
        PaymentService $payments,
        PlanLimitService $planLimits,
    ): View {
        $tenant = TenantContext::current();

        if ($tenant) {
            return $this->tenantIndex($request, $tenant, $payments, $planLimits);
        }

        abort_if((bool) config('libcontrol.license_server.enabled'), 404);

        $paymentUuid = $request->query('payment');
        $paymentUuid = is_string($paymentUuid) ? $paymentUuid : null;
        $summary = $hub->summary($paymentUuid);
        $paymentsList = collect($summary['payments'] ?? []);

        return view('billing.index', [
            'snapshot' => $planLimits->snapshot(),
            'paidUntil' => PlatformSetting::current()->plan_paid_until,
            'plans' => config('libcontrol.plans', []),
            'prices' => $summary['prices'] ?? config('payments.software_plans', []),
            'payments' => $paymentsList->values(),
            'returnedPayment' => $paymentUuid ? $paymentsList->firstWhere('uuid', $paymentUuid) : null,
            'paymentsAvailable' => $hub->available() && ($summary['payments_enabled'] ?? false),
            'hubReachable' => $summary !== null,
            'unavailableReason' => $hub->unavailableReason(),
            'isTrialUpgrade' => false,
            'testAmountPaise' => null,
        ]);
    }

    public function pay(Request $request, HubPaymentClient $hub, PaymentService $payments): RedirectResponse
    {
        $tenant = TenantContext::current();

        if ($tenant) {
            return $this->tenantPay($request, $tenant, $payments);
        }

        abort_if((bool) config('libcontrol.license_server.enabled'), 404);

        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('payments.software_plans', [])))],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $user = $request->user();
        $result = $hub->checkout([
            'purpose' => 'software_plan',
            'item_key' => $validated['plan'],
            'billing_cycle' => $validated['cycle'],
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'return_url' => route('billing.index'),
        ]);

        if (! $result['ok']) {
            return back()->withErrors(['payment' => $result['message']]);
        }

        $this->logActivity(
            $request,
            'billing.checkout_started',
            'Started PhonePe payment for LibControl '.ucfirst($validated['plan']).' ('.$validated['cycle'].').',
        );

        return redirect()->away((string) $result['redirect_url']);
    }

    private function tenantIndex(
        Request $request,
        Tenant $tenant,
        PaymentService $payments,
        PlanLimitService $planLimits,
    ): View {
        $paymentUuid = $request->query('payment');
        $paymentUuid = is_string($paymentUuid) ? $paymentUuid : null;
        $summary = $payments->summaryForTenant($tenant, $paymentUuid);
        $paymentsList = collect($summary['payments'] ?? []);
        $paidUntilRaw = $summary['plan']['plan_paid_until'] ?? null;
        $paidUntil = $paidUntilRaw ? \Illuminate\Support\Carbon::parse($paidUntilRaw) : null;
        $returned = $paymentUuid ? $paymentsList->firstWhere('uuid', $paymentUuid) : null;

        if ($returned && ($returned['status'] ?? null) === 'completed' && ! $tenant->fresh()->is_trial) {
            session()->flash('trial_notice', 'Payment received. Your library is upgraded and fully unlocked.');
        }

        return view('billing.index', [
            'snapshot' => $planLimits->snapshot(),
            'paidUntil' => $paidUntil,
            'plans' => config('libcontrol.plans', []),
            'prices' => $summary['prices'] ?? config('payments.software_plans', []),
            'payments' => $paymentsList->values(),
            'returnedPayment' => $returned,
            'paymentsAvailable' => (bool) ($summary['payments_enabled'] ?? false),
            'hubReachable' => true,
            'unavailableReason' => null,
            'isTrialUpgrade' => (bool) $tenant->is_trial,
            'testAmountPaise' => $summary['test_amount_paise'] ?? null,
        ]);
    }

    private function tenantPay(Request $request, Tenant $tenant, PaymentService $payments): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('payments.software_plans', [])))],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $user = $request->user();

        try {
            $result = $payments->startTenantCheckout($tenant, [
                'item_key' => $validated['plan'],
                'billing_cycle' => $validated['cycle'],
                'payer_name' => $user?->name,
                'payer_email' => $user?->email ?? $tenant->owner_email,
                'return_url' => route('billing.index'),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->logActivity(
            $request,
            'billing.checkout_started',
            'Started PhonePe '.($tenant->is_trial ? 'trial upgrade' : 'renewal').' for LibControl '
                .ucfirst($validated['plan']).' ('.$validated['cycle'].').',
        );

        return redirect()->away((string) $result['redirect_url']);
    }
}
