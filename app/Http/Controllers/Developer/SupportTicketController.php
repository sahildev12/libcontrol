<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $tickets = SupportTicket::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('subject', 'like', "%{$search}%")
                        ->orWhere('library_name', 'like', "%{$search}%")
                        ->orWhere('library_code', 'like', "%{$search}%")
                        ->orWhere('reporter_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('developer.support-tickets.index', compact('tickets', 'status', 'search'));
    }

    public function show(SupportTicket $supportTicket): View
    {
        return view('developer.support-tickets.show', [
            'ticket' => $supportTicket,
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
