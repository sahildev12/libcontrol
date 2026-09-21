<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        $rows = SupportTicket::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'library_name' => $ticket->library_name ?: '—',
                'library_sub' => $ticket->library_code ?: $ticket->deployment_domain ?: '—',
                'subject' => $ticket->subject,
                'category' => $ticket->categoryLabel(),
                'reporter_name' => $ticket->reporter_name,
                'reporter_email' => $ticket->reporter_email,
                'status' => $ticket->status,
                'status_label' => $ticket->statusLabel(),
                'priority' => $ticket->priority,
                'priority_label' => $ticket->priorityLabel(),
                'created_at' => $ticket->created_at?->format('d M Y, h:i A'),
                'unread' => $ticket->isUnread(),
                'show_url' => route('developer.support-tickets.show', $ticket),
            ])
            ->values()
            ->all();

        $stats = [
            'total' => SupportTicket::query()->count(),
            'unread' => SupportTicket::query()->whereNull('read_at')->count(),
            'open' => SupportTicket::query()->where('status', SupportTicket::STATUS_OPEN)->count(),
            'in_progress' => SupportTicket::query()->where('status', SupportTicket::STATUS_IN_PROGRESS)->count(),
            'resolved' => SupportTicket::query()->where('status', SupportTicket::STATUS_RESOLVED)->count(),
        ];

        return view('developer.support-tickets.index', compact('rows', 'stats'));
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->markAsRead();

        return view('developer.support-tickets.show', [
            'ticket' => $supportTicket->fresh(),
        ]);
    }

    public function update(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $supportTicket->update($validated);

        return redirect()
            ->route('developer.support-tickets.show', $supportTicket)
            ->with('status', 'Ticket updated.');
    }
}
