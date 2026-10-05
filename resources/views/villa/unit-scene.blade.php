@php
    $unitName = $unitType->translatedName($locale);
    $beds = collect($unitType->bed_config ?? [])
        ->map(fn ($bed) => $bed['count'].' × '.($unitTerms['bed'][$bed['type']] ?? Str::headline($bed['type'])))
        ->implode(', ');
    $primaryImage = $unitType->images->first();
    $unitUrl = fn ($unit) => route('villa.unit', ['villaSlug' => $villa->slug, 'unitSlug' => $unit->slug, 'lang' => $locale]);
@endphp
<section class="lobby-content @container" aria-label="{{ $unitName }}">
    <a href="{{ route('villa.units', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" data-stage-exit class="panel-close" aria-label="{{ $labels['units_heading'] }}"><span aria-hidden="true">×</span></a>
    <article class="unit-scene">
        <div class="flex flex-wrap items-center justify-between gap-3 pr-12">
            <p class="lobby-eyebrow">{{ $lobby['unit_counter'] }} {{ $unitIndex + 1 }} / {{ $unitTypes->count() }}</p>
            @if ($unitTypes->count() > 1)
                <nav class="flex gap-2" aria-label="{{ $lobby['unit_scene'] }}">
                    <a href="{{ $unitUrl($previousUnit) }}" data-stage-exit class="unit-scene-step" rel="prev"><span aria-hidden="true">←</span> {{ $lobby['prev_unit'] }}</a>
                    <a href="{{ $unitUrl($nextUnit) }}" data-stage-exit class="unit-scene-step" rel="next">{{ $lobby['next_unit'] }} <span aria-hidden="true">→</span></a>
                </nav>
            @endif
        </div>

        @include('villa.narrator', ['text' => $unitNarrations['unit'][$unitType->slug], 'key' => 'unit-'.$unitType->slug])

        <div class="mt-5 grid gap-7 @2xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div data-unit-gallery>
                <div class="aspect-[4/3] w-full overflow-hidden rounded-2xl bg-stone-200">
                    @if ($primaryImage)
                        <img src="{{ $primaryImage->image_source }}" alt="{{ $primaryImage->alt_text }}" class="h-full w-full object-cover" data-unit-gallery-main>
                    @else
                        <div class="grid h-full place-items-center text-sm text-stone-500">{{ $lobby['no_photo'] }}</div>
                    @endif
                </div>
                @if ($unitType->images->count() > 1)
                    <ul class="mt-3 grid grid-cols-4 gap-2" aria-label="{{ $lobby['gallery'] }}">
                        @foreach ($unitType->images as $imageIndex => $image)
                            <li>
                                <button type="button" class="unit-scene-thumb" data-unit-thumb data-src="{{ $image->image_source }}" data-alt="{{ $image->alt_text }}" aria-label="{{ $lobby['photo'] }} {{ $imageIndex + 1 }}" @if($imageIndex === 0) aria-current="true" @endif>
                                    <img src="{{ $image->image_source }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="mt-0!">{{ $unitName }}</h2>
                <p class="mt-3 leading-relaxed text-stone-600">{{ $unitType->translatedDescription($locale) }}</p>

                <p class="mt-5 text-sm text-stone-500">
                    {{ $labels['from'] }}
                    <span class="text-2xl font-semibold text-stone-900">{{ $villa->currency }} {{ number_format((float) $unitType->base_price, 0, ',', '.') }}</span>
                    {{ $labels['per_night'] }}
                </p>
                <p class="mt-1 text-xs text-stone-500">{{ $lobby['availability_note'] }}</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('villa.reservation', ['villaSlug' => $villa->slug, 'lang' => $locale, 'unit' => $unitType->slug]) }}" data-stage-exit data-tour-line="{{ $narration['tour_reservation'] }}" class="lobby-action">{{ $lobby['reserve_unit'] }} <span aria-hidden="true">↗</span></a>
                    <button type="button" class="unit-scene-step" data-hero-quick-message="{{ str_replace(':name', $unitName, $lobby['ask_unit_q']) }}">{{ $lobby['ask_unit'] }}</button>
                </div>

                <dl class="unit-scene-facts">
                    @if ($unitType->size_sqm)
                        <div><dt>{{ $lobby['size'] }}</dt><dd>{{ $unitType->size_sqm }} m²</dd></div>
                    @endif
                    @if ($beds !== '')
                        <div><dt>{{ $lobby['bed'] }}</dt><dd>{{ $beds }}</dd></div>
                    @endif
                    <div><dt>{{ $lobby['guests'] }}</dt><dd>{{ $unitType->maxOccupancy() }} {{ $labels['max_guests'] }}</dd></div>
                    @if ($unitType->view_type)
                        <div><dt>{{ $lobby['view'] }}</dt><dd>{{ $unitTerms['view'][$unitType->view_type] ?? Str::headline($unitType->view_type) }}</dd></div>
                    @endif
                    <div><dt>{{ $lobby['breakfast'] }}</dt><dd>{{ $unitType->breakfast_included ? $labels['breakfast_included'] : $lobby['breakfast_excluded'] }}</dd></div>
                    <div>
                        <dt>{{ $lobby['extra_bed'] }}</dt>
                        <dd>
                            @if ($unitType->extra_bed_available)
                                {{ $lobby['extra_bed_yes'] }}@if ($unitType->extra_bed_price > 0) (+{{ $villa->currency }} {{ number_format((float) $unitType->extra_bed_price, 0, ',', '.') }})@endif
                            @else
                                {{ $lobby['extra_bed_no'] }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if (! empty($unitType->amenities))
                    <h3 class="mt-6 text-sm font-semibold">{{ $lobby['amenities'] }}</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($unitType->amenities as $amenity)
                            <li class="rounded-full border border-stone-300 bg-white px-3 py-1 text-xs text-stone-600">{{ $unitTerms['amenity'][$amenity] ?? Str::headline($amenity) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </article>
</section>
