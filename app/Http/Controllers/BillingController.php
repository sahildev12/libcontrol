<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\Payments\HubPaymentClient;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, HubPaymentClient $hub, PlanLimitService $planLimits): View
    {
        abort_if((bool) config('libcontrol.license_server.enabled'), 404);

        $paymentUuid = $request->query('payment');
        $paymentUuid = is_string($paymentUuid) ? $paymentUuid : null;
        $summary = $hub->summary($paymentUuid);
        $payments = collect($summary['payments'] ?? []);

        return view('billing.index', [
            'snapshot' => $planLimits->snapshot(),
            'paidUntil' => PlatformSetting::current()->plan_paid_until,
            'plans' => config('libcontrol.plans', []),
            'prices' => $summary['prices'] ?? config('payments.software_plans', []),
            'payments' => $payments->values(),
            'returnedPayment' => $paymentUuid ? $payments->firstWhere('uuid', $paymentUuid) : null,
            'paymentsAvailable' => $hub->available() && ($summary['payments_enabled'] ?? false),
            'hubReachable' => $summary !== null,
            'unavailableReason' => $hub->unavailableReason(),
        ]);
    }

    public function pay(Request $request, HubPaymentClient $hub): RedirectResponse
    {
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
}
