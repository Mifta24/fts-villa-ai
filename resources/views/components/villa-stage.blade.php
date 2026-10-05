@props(['villa', 'locale', 'supportedLocales', 'labels', 'lobby', 'narration', 'scene', 'backdrop', 'menuItems', 'title' => null, 'welcome' => null, 'sidebar' => null])
{{-- The fixed full-screen stage every scene shares: framed villa photo, the sand rail with brand, menu and tools, and the concierge chat dock. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $villa->name.' · AI Concierge' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="stage-page antialiased" style="--concierge-image: url('{{ $backdrop['avatarImage'] }}'); --stage-focus: {{ $backdrop['focus'] }}; --stage-focus-mobile: {{ $backdrop['focusMobile'] }}; --concierge-zoom: {{ $backdrop['avatarZoom'] }}; --concierge-focus: {{ $backdrop['avatarFocus'] }}">
    <main class="stage" data-lobby data-scene="{{ $scene }}" data-page="{{ $scene === 'lobby' ? 'home' : $scene }}">
        <div class="stage-loader" data-stage-loader role="status"><span class="villa-monogram" aria-hidden="true">{{ mb_substr($villa->name, 0, 1) }}</span><p>{{ $lobby['loading'] }}</p></div>
        <img src="{{ $backdrop['image'] }}" alt="" class="stage-image" fetchpriority="high">
        <div class="stage-shade"></div>
        <p class="stage-tour" data-stage-tour aria-live="polite"></p>

        {{-- The sand rail: brand, the contents of the stay, and the page tools. --}}
        <aside class="stage-rail" aria-label="{{ $villa->name }}">
            <a href="{{ route('villa.show', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" @if($scene !== 'lobby') data-stage-exit data-tour-line="{{ $narration['tour_lobby'] }}" @endif class="rail-brand">
                <span class="villa-monogram" aria-hidden="true">{{ mb_substr($villa->name, 0, 1) }}</span>
                <span class="min-w-0"><span class="rail-brand-name">{{ $villa->name }}</span><span class="rail-brand-place">{{ $villa->city }} · {{ $villa->country }}</span></span>
            </a>

@if($sidebar)
            {{ $sidebar }}
@else
            <div class="stage-menu">
                <p class="lobby-eyebrow">{{ $lobby['explore'] }}</p>
                <nav class="lobby-navigation" aria-label="{{ $lobby['explore'] }}">
                    @foreach ($menuItems as $item)
                        <a href="{{ $item['href'] }}"
                            @if($item['exit']) data-stage-exit @if($item['tour']) data-tour-line="{{ $item['tour'] }}" @endif data-topic="{{ $item['topic'] }}" @else data-lobby-link="{{ $item['key'] }}" aria-controls="lobby-{{ $item['key'] }}" @endif
                            @if($item['key'] === $scene || ($scene === 'unit' && $item['key'] === 'units') || ($scene === 'facility' && $item['key'] === 'facilities')) aria-current="page" @endif>
                            <span class="nav-index" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="nav-label">{{ $item['label'] }}</span><span class="nav-arrow" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </nav>
            </div>
@endif

            <div class="rail-tools">
                <x-sound-toggle :on="$lobby['sound_on']" :off="$lobby['sound_off']" />
                <nav aria-label="Language" class="stage-lang">
                    @foreach ($supportedLocales as $code)
                        <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'][$code] }}" @if($code === $locale) aria-current="true" @endif>{{ $code }}</a>
                    @endforeach
                </nav>
            </div>
            <span class="stage-menu-footer">{{ $lobby['available'] }} · POWERED BY FTS</span>
        </aside>

        {{ $welcome }}

        {{ $slot }}

        <div data-chat-widget>
            @include('villa.chat')
        </div>
        <noscript><p class="stage-noscript">Aktifkan JavaScript untuk menggunakan navigasi dan AI Concierge. {{ $villa->phone }}</p></noscript>
    </main>
</body>
</html>
