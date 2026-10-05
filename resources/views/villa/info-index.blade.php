@php
    $about = $villa->translatedDescription($locale);
    $mapUrl = $villa->latitude && $villa->longitude
        ? 'https://www.google.com/maps?q='.$villa->latitude.','.$villa->longitude
        : 'https://www.google.com/maps?q='.rawurlencode(trim($villa->address.' '.$villa->city));
    $sections = collect([
        'general' => ['tab' => $lobby['info_tab_about'], 'heading' => $lobby['info_about']],
        'policies' => ['tab' => $lobby['info_policies'], 'heading' => $lobby['info_policies']],
        'faq' => ['tab' => $lobby['info_tab_faq'], 'heading' => $lobby['info_faq']],
    ])->filter(fn ($section, $category) => ($infoItems[$category] ?? collect())->isNotEmpty());
@endphp
<x-villa-stage :villa="$villa" :locale="$locale" :supported-locales="$supportedLocales" :labels="$labels" :lobby="$lobby" :narration="$narration" :scene="$scene" :backdrop="$backdrop" :menu-items="$menuItems" :title="$lobby['menu_info'].' · '.$villa->name">
    <div class="stage-panels">
        <section class="lobby-content info-panel @container" aria-label="{{ $lobby['menu_info'] }}">
            <a href="{{ route('villa.show', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_lobby'] }}" class="panel-close" aria-label="{{ $lobby['back'] }}"><span aria-hidden="true">×</span></a>
            <p class="lobby-eyebrow">{{ $villa->name }}</p>
            <h2>{{ $lobby['menu_info'] }}</h2>
            @include('villa.narrator', ['text' => $sceneNarrations['info'], 'key' => 'info', 'inline' => true])

            @if ($about)
                <p class="mt-4 leading-relaxed text-stone-600">{{ $about }}</p>
            @endif

            {{-- Check-in / check-out, read like the two halves of a keycard sleeve. --}}
            <div class="info-stay">
                <div class="info-stay-item">
                    <span class="info-stay-label">{{ $lobby['check_in'] }}</span>
                    <span class="info-stay-time">{{ substr($villa->check_in_time ?? '', 0, 5) ?: '—' }}</span>
                </div>
                <div class="info-stay-divider" aria-hidden="true"></div>
                <div class="info-stay-item">
                    <span class="info-stay-label">{{ $lobby['check_out'] }}</span>
                    <span class="info-stay-time">{{ substr($villa->check_out_time ?? '', 0, 5) ?: '—' }}</span>
                </div>
            </div>

            <div class="info-facts">
                <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="info-fact">
                    <x-info-icon name="place" />
                    <span>{{ $villa->city ?: $lobby['info_address'] }}</span>
                </a>
                @if ($staffLinks['whatsapp_url'])
                    <a href="{{ $staffLinks['whatsapp_url'] }}" target="_blank" rel="noopener" class="info-fact"><x-info-icon name="contact" /><span>{{ $wizard['whatsapp'] }}</span></a>
                @elseif ($villa->phone)
                    <a href="{{ $staffLinks['phone_url'] }}" class="info-fact"><x-info-icon name="contact" /><span>{{ $villa->phone }}</span></a>
                @endif
            </div>

            <p class="info-address">{{ $villa->address }}, {{ $villa->city }}, {{ $villa->country }}</p>

            @if ($sections->count() > 1)
                <div class="info-tabs">
                    @foreach ($sections as $category => $section)
                        <input type="radio" name="info-tab" id="info-tab-{{ $category }}" class="info-tab-radio" @checked($loop->first)>
                    @endforeach

                    <div class="info-tablist">
                        @foreach ($sections as $category => $section)
                            <label for="info-tab-{{ $category }}">{{ $section['tab'] }}</label>
                        @endforeach
                    </div>

                    <div class="info-tabpanels">
                        @foreach ($sections as $category => $section)
                            <div class="info-tabpanel" data-panel="{{ $category }}">
                                <div class="info-list">
                                    @foreach ($infoItems[$category] as $item)
                                        <details class="info-row">
                                            <summary><span>{{ $item->translatedTitle($locale) }}</span><span aria-hidden="true" class="info-row-chevron">›</span></summary>
                                            <p>{{ $item->translatedBody($locale) }}</p>
                                        </details>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($sections->isNotEmpty())
                @php $only = $sections->keys()->first(); @endphp
                <h3 class="info-section-heading">{{ $sections[$only]['heading'] }}</h3>
                <div class="info-list">
                    @foreach ($infoItems[$only] as $item)
                        <details class="info-row">
                            <summary><span>{{ $item->translatedTitle($locale) }}</span><span aria-hidden="true" class="info-row-chevron">›</span></summary>
                            <p>{{ $item->translatedBody($locale) }}</p>
                        </details>
                    @endforeach
                </div>
            @endif

            <button type="button" data-hero-quick-message="{{ $labels['menu_policies_q'] }}" class="lobby-action mt-8">{{ $lobby['info_ask'] }} <span aria-hidden="true">↗</span></button>
        </section>
    </div>
</x-villa-stage>
