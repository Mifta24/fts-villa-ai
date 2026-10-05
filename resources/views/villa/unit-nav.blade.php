{{-- On the units scenes the rail holds the villa index, so the guest can step between villas without going back. --}}
<div class="stage-menu" role="region" aria-label="{{ $labels['units_heading'] }}">
    <a href="{{ route('villa.show', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_lobby'] }}" class="stage-menu-back"><span aria-hidden="true">‹</span> {{ $lobby['explore'] }}</a>
    <p class="lobby-eyebrow">{{ $labels['units_heading'] }}</p>
    <nav class="unit-nav">
        @foreach ($unitTypes as $item)
            <a href="{{ route('villa.unit', ['villaSlug' => $villa->slug, 'unitSlug' => $item->slug, 'lang' => $locale]) }}"
                data-stage-exit
                @if (($unitType ?? null)?->slug === $item->slug) aria-current="page" @endif>
                @if ($item->images->first())
                    <img class="unit-nav-thumb" src="{{ $item->images->first()->image_source }}" alt="" loading="lazy">
                @endif
                <span class="unit-nav-text">
                    <span class="unit-nav-name">{{ $item->translatedName($locale) }}</span>
                    <span class="unit-nav-price">{{ $labels['from'] }} {{ $villa->currency }} {{ number_format((float) $item->base_price, 0, ',', '.') }} {{ $labels['per_night'] }}</span>
                </span>
            </a>
        @endforeach
    </nav>
</div>
