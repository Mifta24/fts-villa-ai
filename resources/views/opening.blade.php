<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FTS Villa AI</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="stage-page antialiased">
    <main class="stage stage-opening">
        <div class="stage-loader" data-stage-loader role="status"><span class="villa-monogram" aria-hidden="true">F</span><p>{{ $opening['loading'] }}</p></div>
        <img src="{{ $openingImage }}" alt="" class="stage-image" fetchpriority="high">
        <div class="stage-shade"></div>

        <header class="opening-top">
            <span class="rail-brand">
                <span class="villa-monogram" aria-hidden="true">F</span>
                <span class="rail-brand-name">FTS Villa AI</span>
            </span>
            <div class="rail-tools">
                <x-sound-toggle :on="$opening['sound_on']" :off="$opening['sound_off']" />
                <nav aria-label="Language" class="stage-lang">
                    @foreach ($supportedLocales as $code)
                        <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'][$code] }}" @if($code === $locale) aria-current="true" @endif>{{ $code }}</a>
                    @endforeach
                </nav>
            </div>
        </header>

        <section class="opening-panel" aria-labelledby="opening-title">
            <p class="lobby-eyebrow">AI CONCIERGE</p>
            <h1 id="opening-title">{{ $opening['welcome'] }}<br><em>FTS Villa AI</em></h1>
            <p class="opening-tagline">{{ $opening['tagline'] }}</p>

            @if ($villas->count() === 1)
                @php
                    $villaSlug = $villas->first()->slug;
                    $openingLinks = [
                        'units' => route('villa.units', ['villaSlug' => $villaSlug, 'lang' => $locale]),
                        'facilities' => route('villa.facilities', ['villaSlug' => $villaSlug, 'lang' => $locale]),
                        'reservation' => route('villa.reservation', ['villaSlug' => $villaSlug, 'lang' => $locale]),
                        'staff' => route('villa.staff', ['villaSlug' => $villaSlug, 'lang' => $locale]),
                    ];
                @endphp
                <ul class="opening-links">
                    @foreach ($openingLinks as $key => $href)
                        <li><a data-stage-exit href="{{ $href }}"><span>{{ $opening[$key] }}</span><span aria-hidden="true">›</span></a></li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="opening-enter">
            @forelse ($villas as $villa)
                <a href="{{ route('villa.show', ['villaSlug' => $villa->slug, 'lang' => $locale]) }}" class="opening-button" data-stage-exit>
                    {{ str_replace(':name', $villa->name, $opening['enter']) }} <span aria-hidden="true">→</span>
                </a>
            @empty
                <p class="opening-empty">{{ $opening['empty'] }}</p>
            @endforelse
        </div>
    </main>
</body>
</html>
