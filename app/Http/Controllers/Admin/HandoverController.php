<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentVilla;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HandoverController extends Controller
{
    use ResolvesCurrentVilla;

    public function index(Request $request): View
    {
        $villa = $this->currentVilla($request);

        $status = $request->query('status', HandoverRequest::STATUS_OPEN);

        $handovers = HandoverRequest::whereHas('conversation', fn ($q) => $q->where('villa_id', $villa->id))
            ->where('status', $status)
            ->with('conversation')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.handovers.index', compact('villa', 'handovers', 'status'));
    }

    public function show(Request $request, HandoverRequest $handover): View
    {
        $villa = $this->currentVilla($request);
        abort_if($handover->conversation->villa_id !== $villa->id, 404);

        $handover->load('conversation.messages');

        return view('admin.handovers.show', compact('villa', 'handover'));
    }

    public function reply(Request $request, HandoverRequest $handover): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($handover->conversation->villa_id !== $villa->id, 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $handover->conversation->messages()->create([
            'role' => ConversationMessage::ROLE_STAFF,
            'content' => $data['message'],
        ]);

        return back()->with('status', 'Reply sent to the guest.');
    }

    public function resolve(Request $request, HandoverRequest $handover): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($handover->conversation->villa_id !== $villa->id, 404);

        $handover->update(['status' => HandoverRequest::STATUS_RESOLVED, 'resolved_at' => now(), 'assigned_to' => $request->user()->id]);
        $handover->conversation->update(['status' => Conversation::STATUS_ACTIVE]);

        return redirect()->route('admin.handovers.index')->with('status', 'Handover resolved — the AI Concierge is back in the conversation.');
    }
}
