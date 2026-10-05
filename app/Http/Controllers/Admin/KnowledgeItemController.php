<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentVilla;
use App\Http\Controllers\Controller;
use App\Models\VillaKnowledgeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeItemController extends Controller
{
    use ResolvesCurrentVilla;

    public function index(Request $request): View
    {
        $villa = $this->currentVilla($request);

        $items = $villa->knowledgeItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.knowledge-items.index', compact('villa', 'items'));
    }

    public function create(Request $request): View
    {
        $villa = $this->currentVilla($request);

        return view('admin.knowledge-items.form', [
            'villa' => $villa,
            'item' => new VillaKnowledgeItem(['category' => VillaKnowledgeItem::CATEGORY_GENERAL, 'is_active' => true]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $villa = $this->currentVilla($request);

        $item = $villa->knowledgeItems()->create($this->validated($request));

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$item->title}\" created.");
    }

    public function edit(Request $request, VillaKnowledgeItem $knowledgeItem): View
    {
        $villa = $this->currentVilla($request);
        abort_if($knowledgeItem->villa_id !== $villa->id, 404);

        return view('admin.knowledge-items.form', [
            'villa' => $villa,
            'item' => $knowledgeItem,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, VillaKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($knowledgeItem->villa_id !== $villa->id, 404);

        $knowledgeItem->update($this->validated($request));

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$knowledgeItem->title}\" updated.");
    }

    public function destroy(Request $request, VillaKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($knowledgeItem->villa_id !== $villa->id, 404);

        $knowledgeItem->delete();

        return redirect()->route('admin.knowledge-items.index')->with('status', 'Knowledge item deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', $this->categories())],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'translations.en.title' => ['nullable', 'string'],
            'translations.en.body' => ['nullable', 'string'],
            'translations.ja.title' => ['nullable', 'string'],
            'translations.ja.body' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'category' => $data['category'],
            'title' => $data['title'],
            'body' => $data['body'],
            'translations' => [
                'en' => ['title' => $data['translations']['en']['title'] ?? null, 'body' => $data['translations']['en']['body'] ?? null],
                'ja' => ['title' => $data['translations']['ja']['title'] ?? null, 'body' => $data['translations']['ja']['body'] ?? null],
            ],
            'tags' => collect(explode(',', (string) ($data['tags'] ?? '')))->map(fn ($t) => trim($t))->filter()->values()->all(),
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function categories(): array
    {
        return [
            VillaKnowledgeItem::CATEGORY_GENERAL,
            VillaKnowledgeItem::CATEGORY_FACILITIES,
            VillaKnowledgeItem::CATEGORY_POLICIES,
            VillaKnowledgeItem::CATEGORY_DINING,
            VillaKnowledgeItem::CATEGORY_TRANSPORT,
            VillaKnowledgeItem::CATEGORY_FAQ,
        ];
    }
}
