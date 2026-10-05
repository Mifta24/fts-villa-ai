<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems" :title="$labels['menu_facilities'].' · '.$villa->name">
    <div class="stage-panels">
        <section class="lobby-content stage-panel-right @container" aria-label="{{ $labels['menu_facilities'] }}">
            <a href="{{ route('villa.show', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_lobby'] }}" class="panel-close" aria-label="{{ $lobby['back'] }}"><span aria-hidden="true">×</span></a>
            <p class="lobby-eyebrow">{{ $labels['chat_heading'] }}</p>
            <h2>{{ $labels['menu_facilities'] }}</h2>
            @if ($sceneNarrations['facilities'])
                @include('villa.narrator', ['text' => $sceneNarrations['facilities'], 'key' => 'facilities', 'inline' => true])
            @endif

            <div class="facility-grid">
                @forelse ($facilities as $item)
                    @php $title = $item->translatedTitle($locale); @endphp
                    <a href="{{ route('villa.facility', ['villaSlug' => $villa->slug, 'facilityId' => $item->id, 'lang' => $locale]) }}" data-stage-exit class="facility-card">
                        <span class="facility-card-photo">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy">
                            @endif
                            <span class="facility-card-icon"><x-facility-icon :name="$item->iconName()" /></span>
                        </span>
                        <span class="facility-card-body">
                            <span class="facility-card-title">{{ $title }}</span>
                            <span class="facility-card-text">{{ $item->translatedBody($locale) }}</span>
                        </span>
                        <span class="facility-card-go" aria-hidden="true">›</span>
                    </a>
                @empty
                    <p class="text-stone-500">{{ $lobby['empty'] }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-villa-stage>
