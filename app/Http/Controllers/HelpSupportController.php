<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpSupportController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->when($request->user(), fn ($query) => $query->where('reporter_user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('help-support.index', [
            'tickets' => $tickets,
            'supportEmail' => config('libcontrol.support.email'),
            'supportPhone' => config('libcontrol.support.phone'),
            'companyUrl' => config('libcontrol.product.company_url'),
        ]);
    }

    public function store(StoreSupportTicketRequest $request, SupportTicketSyncService $syncService): JsonResponse
    {
        $user = $request->user();
        $settings = \App\Models\PlatformSetting::current();

        $ticket = SupportTicket::query()->create([
            'subject' => $request->string('subject')->toString(),
            'message' => $request->string('message')->toString(),
            'category' => $request->string('category')->toString(),
            'priority' => $request->string('priority')->toString(),
            'status' => SupportTicket::STATUS_OPEN,
            'reporter_user_id' => $user?->id,
            'reporter_name' => $user?->name ?: 'Library Admin',
            'reporter_email' => $user?->email ?: $request->string('reporter_email')->toString(),
            'library_code' => $settings->library_code,
            'library_name' => $settings->displayName(),
        ]);

        $synced = $syncService->push($ticket);

        return response()->json([
            'message' => $synced
                ? 'Support ticket submitted. Our team will respond soon.'
                : 'Ticket saved locally. We could not reach Phenomit right now, but your request is recorded.',
            'ticket' => [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'status_label' => $ticket->statusLabel(),
                'created_at' => $ticket->created_at?->format('d M Y, h:i A'),
                'synced' => $synced,
            ],
        ], 201);
    }
}
