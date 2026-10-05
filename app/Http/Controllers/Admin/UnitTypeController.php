<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentVilla;
use App\Http\Controllers\Controller;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UnitTypeController extends Controller
{
    use ResolvesCurrentVilla;

    public function index(Request $request): View
    {
        $villa = $this->currentVilla($request);

        $unitTypes = $villa->unitTypes()->withCount('images')->orderBy('sort_order')->get();

        return view('admin.unit-types.index', compact('villa', 'unitTypes'));
    }

    public function create(Request $request): View
    {
        $villa = $this->currentVilla($request);

        return view('admin.unit-types.form', [
            'villa' => $villa,
            'unitType' => new UnitType(['max_adults' => 2, 'max_children' => 0, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        $data = $this->validated($request);

        $unitType = $villa->unitTypes()->create($data);
        $this->syncImages($unitType, $request);

        return redirect()->route('admin.unit-types.index')->with('status', "Villa type \"{$unitType->name}\" created.");
    }

    public function edit(Request $request, UnitType $unitType): View
    {
        $villa = $this->currentVilla($request);
        abort_if($unitType->villa_id !== $villa->id, 404);

        $unitType->load('images');

        return view('admin.unit-types.form', compact('villa', 'unitType'));
    }

    public function update(Request $request, UnitType $unitType): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($unitType->villa_id !== $villa->id, 404);

        $unitType->update($this->validated($request, $unitType));
        $this->syncImages($unitType, $request);

        return redirect()->route('admin.unit-types.index')->with('status', "Villa type \"{$unitType->name}\" updated.");
    }

    public function destroy(Request $request, UnitType $unitType): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($unitType->villa_id !== $villa->id, 404);

        $unitType->delete();

        return redirect()->route('admin.unit-types.index')->with('status', 'Villa type deleted.');
    }

    private function validated(Request $request, ?UnitType $unitType = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'translations.en.name' => ['nullable', 'string'],
            'translations.en.description' => ['nullable', 'string'],
            'translations.ja.name' => ['nullable', 'string'],
            'translations.ja.description' => ['nullable', 'string'],
            'size_sqm' => ['nullable', 'integer', 'min:1'],
            'max_adults' => ['required', 'integer', 'min:1'],
            'max_children' => ['required', 'integer', 'min:0'],
            'bed_type_1' => ['nullable', 'string', 'max:50'],
            'bed_count_1' => ['nullable', 'integer', 'min:1'],
            'bed_type_2' => ['nullable', 'string', 'max:50'],
            'bed_count_2' => ['nullable', 'integer', 'min:1'],
            'view_type' => ['nullable', 'string', 'max:50'],
            'breakfast_included' => ['sometimes', 'boolean'],
            'extra_bed_available' => ['sometimes', 'boolean'],
            'extra_bed_price' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'amenities' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $bedConfig = [];
        if (! empty($data['bed_type_1'])) {
            $bedConfig[] = ['type' => $data['bed_type_1'], 'count' => (int) ($data['bed_count_1'] ?? 1)];
        }
        if (! empty($data['bed_type_2'])) {
            $bedConfig[] = ['type' => $data['bed_type_2'], 'count' => (int) ($data['bed_count_2'] ?? 1)];
        }

        $amenities = collect(explode(',', (string) ($data['amenities'] ?? '')))
            ->map(fn ($a) => Str::of($a)->trim()->slug('_')->toString())
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $data['name'],
            'slug' => $unitType?->slug ?? $this->uniqueSlug($request->user()->currentVilla(), $data['name']),
            'description' => $data['description'] ?? null,
            'translations' => [
                'en' => ['name' => $data['translations']['en']['name'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['name' => $data['translations']['ja']['name'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
            'size_sqm' => $data['size_sqm'] ?? null,
            'max_adults' => $data['max_adults'],
            'max_children' => $data['max_children'],
            'bed_config' => $bedConfig,
            'view_type' => $data['view_type'] ?? null,
            'breakfast_included' => $request->boolean('breakfast_included'),
            'extra_bed_available' => $request->boolean('extra_bed_available'),
            'extra_bed_price' => $data['extra_bed_price'] ?? null,
            'base_price' => $data['base_price'],
            'amenities' => $amenities,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug($villa, string $name): string
    {
        $base = Str::slug($name) ?: 'unit';
        $slug = $base;
        $suffix = 1;

        while ($villa->unitTypes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncImages(UnitType $unitType, Request $request): void
    {
        $deleteIds = collect($request->input('delete_images', []))->map(fn ($id) => (int) $id);
        if ($deleteIds->isNotEmpty()) {
            $unitType->images()->whereIn('id', $deleteIds)->delete();
        }

        $newImages = collect($request->input('new_images', []))
            ->filter(fn ($row) => filled($row['url'] ?? null));

        $sort = $unitType->images()->max('sort_order') + 1;
        foreach ($newImages as $row) {
            $unitType->images()->create([
                'image_url' => $row['url'],
                'alt_text' => $row['alt'] ?? $unitType->name,
                'tags' => collect(explode(',', $row['tags'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all(),
                'sort_order' => $sort++,
            ]);
        }
    }
}
