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
            ->with('attachments')
            ->when($request->user(), fn ($query) => $query->where('reporter_user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('help-support.index', [
            'tickets' => $tickets->map(fn (SupportTicket $ticket) => $this->serializeTicket($ticket)),
            'supportEmail' => config('libcontrol.support.email'),
            'companyUrl' => config('libcontrol.product.company_url'),
            'whatsappUrl' => $this->whatsappUrl(config('libcontrol.support.whatsapp')),
            'faqUrl' => config('libcontrol.support.faq_url'),
            'articlesUrl' => config('libcontrol.support.articles_url'),
            'documentationUrl' => config('libcontrol.support.documentation_url'),
            'supportIllustration' => asset('images/support-agent.png'),
        ]);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless((int) $supportTicket->reporter_user_id === (int) $request->user()?->id, 403);

        $supportTicket->load('attachments');

        return response()->json([
            'ticket' => $this->serializeTicket($supportTicket),
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
            'priority' => $request->string('priority')->toString() ?: 'normal',
            'status' => SupportTicket::STATUS_OPEN,
            'reporter_user_id' => $user?->id,
            'reporter_name' => $user?->name ?: 'Library Admin',
            'reporter_email' => $user?->email ?: $request->string('reporter_email')->toString(),
            'library_code' => $settings->library_code,
            'library_name' => $settings->displayName(),
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('support-tickets/'.$ticket->id, 'public');

            $ticket->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        }

        $ticket->load('attachments');
        $synced = $syncService->push($ticket);

        return response()->json([
            'message' => $synced
                ? 'Support ticket submitted. Our team will respond soon.'
                : 'Ticket saved locally. We could not reach Phenomit right now, but your request is recorded.',
            'ticket' => $this->serializeTicket($ticket, $synced),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTicket(SupportTicket $ticket, ?bool $synced = null): array
    {
        return [
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'status_label' => $ticket->statusLabel(),
            'category' => $ticket->category,
            'category_label' => $ticket->categoryLabel(),
            'priority' => $ticket->priority,
            'priority_label' => $ticket->priorityLabel(),
            'created_at' => $ticket->created_at?->format('d M Y'),
            'created_at_full' => $ticket->created_at?->format('d M Y, h:i A'),
            'updated_at' => $ticket->updated_at?->format('d M Y'),
            'updated_at_full' => $ticket->updated_at?->format('d M Y, h:i A'),
            'synced' => $synced ?? $ticket->synced_at !== null,
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'url' => $attachment->url(),
                'size' => $attachment->size,
            ])->values()->all(),
        ];
    }

    private function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return 'https://wa.me/'.$digits.'?text='.urlencode('Hi Phenomit, I need help with LibControl.');
    }
}
