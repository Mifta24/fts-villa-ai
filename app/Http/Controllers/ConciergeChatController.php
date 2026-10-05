<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Villa;
use App\Services\Concierge\ConciergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConciergeChatController extends Controller
{
    private const SUPPORTED_LOCALES = ['id', 'en', 'ja'];

    public function __construct(private readonly ConciergeService $concierge) {}

    public function start(Request $request, string $villaSlug): JsonResponse
    {
        $villa = $this->publishedVilla($villaSlug);

        $locale = in_array($request->input('locale'), self::SUPPORTED_LOCALES, true)
            ? $request->input('locale')
            : $villa->default_locale;

        $conversation = $this->concierge->startConversation($villa, $locale);

        return response()->json([
            'guest_token' => $conversation->guest_token,
            'locale' => $conversation->locale,
        ]);
    }

    public function message(Request $request, string $villaSlug): JsonResponse
    {
        $villa = $this->publishedVilla($villaSlug);

        $data = $request->validate([
            'guest_token' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:2000'],
            'scene' => ['nullable', Rule::in(Conversation::SCENES)],
            'selected_unit' => ['nullable', 'string', 'max:120'],
            'selected_facility' => ['nullable', 'integer', 'min:1'],
            'reservation' => ['nullable', 'array'],
            'reservation.check_in' => ['nullable', 'date_format:Y-m-d'],
            'reservation.check_out' => ['nullable', 'date_format:Y-m-d'],
            'reservation.adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'reservation.children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'reservation.units' => ['nullable', 'integer', 'min:1', 'max:10'],
            'reservation.unit_type_slug' => ['nullable', 'string', 'max:120'],
        ]);

        $conversation = $this->findConversation($villa, $data['guest_token']);

        $this->concierge->rememberContext($villa, $conversation, [
            'scene' => $data['scene'] ?? null,
            'selected_facility' => $data['selected_facility'] ?? null,
            'selected_unit' => $data['selected_unit'] ?? ($data['reservation']['unit_type_slug'] ?? null),
            ...array_key_exists('reservation', $data) ? ['reservation' => array_filter($data['reservation'] ?? [], fn ($value) => $value !== null && $value !== '')] : [],
        ]);

        try {
            $assistantMessage = $this->concierge->reply($villa, $conversation, $data['message']);
        } catch (\Throwable $e) {
            Log::error('Concierge reply failed', ['villa_id' => $villa->id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'concierge_unavailable',
                'message' => 'The AI Concierge is temporarily unavailable. Please try again in a moment.',
            ], 503);
        }

        return response()->json([
            'message' => $this->formatMessage($assistantMessage),
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function history(Request $request, string $villaSlug): JsonResponse
    {
        $villa = $this->publishedVilla($villaSlug);

        $data = $request->validate(['guest_token' => ['required', 'uuid']]);

        $conversation = $this->findConversation($villa, $data['guest_token']);

        $messages = $conversation->messages()
            ->whereIn('role', [ConversationMessage::ROLE_GUEST, ConversationMessage::ROLE_ASSISTANT, ConversationMessage::ROLE_SYSTEM])
            ->orderBy('created_at')
            ->get()
            ->map(fn (ConversationMessage $m) => $this->formatMessage($m))
            ->values();

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
        ]);
    }

    private function publishedVilla(string $villaSlug): Villa
    {
        $villa = Villa::where('slug', $villaSlug)->first();

        abort_if(! $villa || ! $villa->isPublished(), 404);

        return $villa;
    }

    private function findConversation(Villa $villa, string $guestToken): Conversation
    {
        $conversation = Conversation::where('villa_id', $villa->id)
            ->where('guest_token', $guestToken)
            ->first();

        if (! $conversation) {
            throw ValidationException::withMessages([
                'guest_token' => 'This conversation no longer exists. Please start a new one.',
            ]);
        }

        return $conversation;
    }

    private function formatMessage(ConversationMessage $message): array
    {
        return [
            'role' => $message->role,
            'content' => $message->content,
            'ui_payload' => $message->ui_payload,
            'suggested_actions' => $this->suggestedActions($message->ui_payload),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Interface actions that follow from what the concierge just showed, so
     * the guest can step into the matching scene instead of typing again.
     *
     * @param  list<array<string, mixed>>|null  $uiPayload
     * @return list<array{action: string, unit?: string}>
     */
    private function suggestedActions(?array $uiPayload): array
    {
        $actions = [];

        foreach ($uiPayload ?? [] as $payload) {
            $type = $payload['type'] ?? null;
            $unit = $payload['unit']['unit_type_slug'] ?? $payload['quote']['unit_type_slug'] ?? null;

            if ($type === 'unit_detail' && $unit) {
                $actions[] = ['action' => 'view_unit', 'unit' => $unit];
                $actions[] = ['action' => 'reserve', 'unit' => $unit];
            } elseif ($type === 'availability' && ($payload['available'] ?? false) && $unit) {
                $actions[] = ['action' => 'reserve', 'unit' => $unit];
            } elseif ($type === 'handover') {
                $actions[] = ['action' => 'staff'];
            }
        }

        return collect($actions)->unique(fn (array $action) => $action['action'].($action['unit'] ?? ''))->values()->all();
    }
}
