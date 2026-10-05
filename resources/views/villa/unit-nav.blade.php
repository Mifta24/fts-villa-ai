{{-- On the units scenes the menu card becomes the unit index, so the guest can step between units without going back. --}}
<aside class="stage-menu" aria-label="{{ $labels['units_heading'] }}">
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
    <span class="stage-menu-footer">{{ $lobby['available'] }} · POWERED BY FTS</span>
</aside>
