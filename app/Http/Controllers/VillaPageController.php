<?php

namespace App\Http\Controllers;

use App\Models\Villa;
use App\Models\VillaKnowledgeItem;
use App\Models\UnitType;
use App\Services\Reservation\ReservationHandover;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VillaPageController extends Controller
{
    private const SUPPORTED_LOCALES = ['id', 'en', 'ja'];

    /** The default cut-out of the concierge, layered in front of a scene's background when a scene does not name its own. */
    private const CHARACTER_IMAGE = 'images/character.png';

    public function __construct(private readonly ReservationHandover $handover) {}

    /**
     * What the AI concierge says when the guest steps into the units scenes.
     * Every sentence is built from stored villa data, never generated.
     *
     * @var array<string, array<string, string>>
     */
    private const NARRATION = [
        'en' => [
            'units' => 'Here are our villas. We have :count villa types, from :price per night. Open any of them and I will walk you through it, or ask me anything.',
            'units_one' => 'Here is our villa. We have one villa type, from :price per night. Open it and I will walk you through it, or ask me anything.',
            'size_guests' => 'It offers :size m² for up to :guests guests.',
            'guests' => 'It welcomes up to :guests guests.',
            'breakfast' => 'Breakfast is included.',
            'price' => 'Rates start from :price per night. Final availability and rates are confirmed by our team.',
            'facilities' => 'We have :count facilities and services, including :examples. Choose one to read the details, or ask me anything.',
            'facilities_one' => 'We offer :examples. Open it to read the details, or ask me anything.',
            'info_welcome' => 'Welcome to :villa.',
            'info_place' => 'We are located in :location.',
            'info_hours' => 'Check-in is from :in and check-out is at :out.',
            'info_more' => 'Below you will find the address, our policies and frequently asked questions — or just ask me.',
            'tour_units' => 'This way — let me show you our villas.',
            'tour_facilities' => 'Come, let me show you the villa facilities.',
            'tour_info' => 'Let me tell you more about the villa.',
            'tour_staff' => 'Let me bring you to our team.',
            'tour_reservation' => 'Let me help you plan your stay.',
            'tour_lobby' => 'Let me walk you back to the lobby.',
            'listen' => 'Listen', 'stop' => 'Stop', 'skip' => 'Skip', 'speaks' => 'is speaking',
        ],
        'id' => [
            'units' => 'Inilah villa-villa kami. Ada :count tipe villa, mulai dari :price per malam. Buka salah satu, nanti saya jelaskan — atau tanyakan apa saja kepada saya.',
            'units_one' => 'Inilah villa kami. Ada satu tipe villa, mulai dari :price per malam. Buka villanya, nanti saya jelaskan — atau tanyakan apa saja kepada saya.',
            'size_guests' => 'Luasnya :size m² untuk maksimal :guests tamu.',
            'guests' => 'Villa ini untuk maksimal :guests tamu.',
            'breakfast' => 'Sudah termasuk sarapan.',
            'price' => 'Tarif mulai dari :price per malam. Ketersediaan dan tarif final dikonfirmasi oleh tim kami.',
            'facilities' => 'Kami memiliki :count fasilitas dan layanan, di antaranya :examples. Pilih salah satu untuk membaca detailnya, atau tanyakan apa saja kepada saya.',
            'facilities_one' => 'Kami memiliki :examples. Buka untuk membaca detailnya, atau tanyakan apa saja kepada saya.',
            'info_welcome' => 'Selamat datang di :villa.',
            'info_place' => 'Kami berada di :location.',
            'info_hours' => 'Check-in mulai pukul :in dan check-out pukul :out.',
            'info_more' => 'Di bawah ini ada alamat, kebijakan, dan pertanyaan yang sering diajukan — atau tanyakan langsung kepada saya.',
            'tour_units' => 'Mari, saya antar ke villa-villa kami.',
            'tour_facilities' => 'Mari, saya antar ke fasilitas villa kami.',
            'tour_info' => 'Mari, saya ceritakan lebih lanjut tentang villa kami.',
            'tour_staff' => 'Mari, saya antar Anda bertemu tim kami.',
            'tour_reservation' => 'Mari, saya bantu rencanakan menginap Anda.',
            'tour_lobby' => 'Mari saya antar kembali ke lobi.',
            'listen' => 'Dengarkan', 'stop' => 'Berhenti', 'skip' => 'Lewati', 'speaks' => 'sedang berbicara',
        ],
        'ja' => [
            'units' => 'こちらがヴィラです。:count タイプ、1泊 :price からご用意しています。ヴィラを開いていただければ、ご案内いたします。ご質問もお気軽にどうぞ。',
            'units_one' => 'こちらがヴィラです。1タイプ、1泊 :price からご用意しています。ヴィラを開いていただければ、ご案内いたします。ご質問もお気軽にどうぞ。',
            'size_guests' => '広さは:size m²、最大:guests名様までご利用いただけます。',
            'guests' => '最大:guests名様までご利用いただけます。',
            'breakfast' => '朝食付きです。',
            'price' => '料金は1泊 :price からです。空き状況と料金は、スタッフが最終確認いたします。',
            'facilities' => ':count件の施設・サービスをご用意しています。:examples などです。選ぶと詳細をご覧いただけます。ご質問もどうぞ。',
            'facilities_one' => ':examples をご用意しています。詳細をご覧ください。ご質問もどうぞ。',
            'info_welcome' => 'ようこそ、:villa へ。',
            'info_place' => '所在地は:locationです。',
            'info_hours' => 'チェックインは:in以降、チェックアウトは:outまでです。',
            'info_more' => '以下に、所在地、ご利用規定、よくあるご質問をご案内しています。お気軽にお尋ねください。',
            'tour_units' => 'こちらへどうぞ。ヴィラへご案内します。',
            'tour_facilities' => 'こちらへどうぞ。ヴィラの施設へご案内します。',
            'tour_info' => 'ヴィラについてご案内します。',
            'tour_staff' => 'スタッフのもとへご案内します。',
            'tour_reservation' => 'ご宿泊のご計画をお手伝いします。',
            'tour_lobby' => 'ロビーへご案内します。',
            'listen' => '音声で聞く', 'stop' => '停止', 'skip' => 'スキップ', 'speaks' => '話しています',
        ],
    ];

    /**
     * Guest-facing names for the coded unit values stored in the database.
     *
     * @var array<string, array{bed: array<string, string>, view: array<string, string>, amenity: array<string, string>}>
     */
    private const UNIT_TERMS = [
        'en' => [
            'bed' => ['king' => 'King bed', 'queen' => 'Queen bed', 'twin' => 'Twin bed', 'single' => 'Single bed', 'double' => 'Double bed'],
            'view' => ['garden' => 'Garden view', 'ocean' => 'Ocean view', 'pool' => 'Pool view', 'city' => 'City view', 'mountain' => 'Mountain view'],
            'amenity' => ['air_conditioning' => 'Air conditioning', 'wifi' => 'Wi-Fi', 'minibar' => 'Minibar', 'safe_deposit_box' => 'Safe deposit box', 'balcony' => 'Balcony', 'bathtub' => 'Bathtub', 'coffee_maker' => 'Coffee maker', 'living_room' => 'Living room', 'private_pool' => 'Private pool', 'kitchen' => 'Kitchen', 'outdoor_shower' => 'Outdoor shower', 'gazebo' => 'Gazebo', 'butler_service' => 'Butler service'],
        ],
        'id' => [
            'bed' => ['king' => 'Tempat tidur king', 'queen' => 'Tempat tidur queen', 'twin' => 'Tempat tidur twin', 'single' => 'Tempat tidur single', 'double' => 'Tempat tidur double'],
            'view' => ['garden' => 'Pemandangan taman', 'ocean' => 'Pemandangan laut', 'pool' => 'Pemandangan kolam', 'city' => 'Pemandangan kota', 'mountain' => 'Pemandangan gunung'],
            'amenity' => ['air_conditioning' => 'AC', 'wifi' => 'Wi-Fi', 'minibar' => 'Minibar', 'safe_deposit_box' => 'Brankas', 'balcony' => 'Balkon', 'bathtub' => 'Bak mandi', 'coffee_maker' => 'Mesin kopi', 'living_room' => 'Ruang tamu', 'private_pool' => 'Kolam renang pribadi', 'kitchen' => 'Dapur', 'outdoor_shower' => 'Shower terbuka', 'gazebo' => 'Gazebo', 'butler_service' => 'Layanan butler'],
        ],
        'ja' => [
            'bed' => ['king' => 'キングベッド', 'queen' => 'クイーンベッド', 'twin' => 'ツインベッド', 'single' => 'シングルベッド', 'double' => 'ダブルベッド'],
            'view' => ['garden' => 'ガーデンビュー', 'ocean' => 'オーシャンビュー', 'pool' => 'プールビュー', 'city' => 'シティビュー', 'mountain' => 'マウンテンビュー'],
            'amenity' => ['air_conditioning' => 'エアコン', 'wifi' => 'Wi-Fi', 'minibar' => 'ミニバー', 'safe_deposit_box' => 'セーフティボックス', 'balcony' => 'バルコニー', 'bathtub' => 'バスタブ', 'coffee_maker' => 'コーヒーメーカー', 'living_room' => 'リビングルーム', 'private_pool' => 'プライベートプール', 'kitchen' => 'キッチン', 'outdoor_shower' => '屋外シャワー', 'gazebo' => 'ガゼボ', 'butler_service' => 'バトラーサービス'],
        ],
    ];

    public function index(Request $request): View
    {
        $villas = Villa::where('public_status', 'published')->orderBy('name')->get();

        $locale = in_array($request->query('lang'), self::SUPPORTED_LOCALES, true)
            ? $request->query('lang')
            : ($villas->first()?->default_locale ?? 'id');

        return view('opening', [
            'villas' => $villas,
            'locale' => $locale,
            'supportedLocales' => self::SUPPORTED_LOCALES,
            'opening' => $this->openingLabels($locale),
        ]);
    }

    public function show(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.show', $this->stageData($villa, $locale, 'lobby'));
    }

    public function units(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.units-index', $this->stageData($villa, $locale, 'units'));
    }

    public function unit(Request $request, string $villaSlug, string $unitSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        $data = $this->stageData($villa, $locale, 'unit');
        $units = $data['unitTypes'];
        $index = $units->search(fn (UnitType $unitType) => $unitType->slug === $unitSlug);

        abort_if($index === false, 404);

        return view('villa.unit-detail', [
            ...$data,
            'unitType' => $units[$index],
            'unitIndex' => $index,
            'previousUnit' => $units[($index - 1 + $units->count()) % $units->count()],
            'nextUnit' => $units[($index + 1) % $units->count()],
        ]);
    }

    public function facilities(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.facilities-index', $this->stageData($villa, $locale, 'facilities'));
    }

    public function facility(Request $request, string $villaSlug, int $facilityId): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        $data = $this->stageData($villa, $locale, 'facility');
        $facilities = $data['facilities'];
        $index = $facilities->search(fn (VillaKnowledgeItem $item) => $item->id === $facilityId);

        abort_if($index === false, 404);

        return view('villa.facility-detail', [
            ...$data,
            'facility' => $facilities[$index],
            'facilityIndex' => $index,
            'previousFacility' => $facilities[($index - 1 + $facilities->count()) % $facilities->count()],
            'nextFacility' => $facilities[($index + 1) % $facilities->count()],
        ]);
    }

    public function info(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.info-index', $this->stageData($villa, $locale, 'info'));
    }

    public function staff(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.staff-index', $this->stageData($villa, $locale, 'staff'));
    }

    public function reservationScene(Request $request, string $villaSlug): View
    {
        [$villa, $locale] = $this->resolveStage($request, $villaSlug);

        return view('villa.reservation-index', [
            ...$this->stageData($villa, $locale, 'reservation'),
            'preselectedUnit' => (string) $request->query('unit', ''),
        ]);
    }

    /**
     * @return array{0: Villa, 1: string}
     */
    private function resolveStage(Request $request, string $villaSlug): array
    {
        $villa = Villa::where('slug', $villaSlug)->firstOrFail();

        abort_if(! $villa->isPublished(), 404);

        $locale = in_array($request->query('lang'), self::SUPPORTED_LOCALES, true)
            ? $request->query('lang')
            : $villa->default_locale;

        return [$villa, $locale];
    }

    /**
     * Everything the shared stage shell needs, for whichever scene the guest
     * has walked into.
     *
     * @return array<string, mixed>
     */
    private function stageData(Villa $villa, string $locale, string $scene): array
    {
        $unitTypes = $villa->unitTypes()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->values();

        $facilities = $villa->knowledgeItems()->where('is_active', true)
            ->whereIn('category', ['facilities', 'dining', 'transport'])
            ->orderBy('sort_order')->get()->values();

        $labels = $this->labels($locale);
        $lobby = $this->lobbyLabels($locale);

        return [
            'villa' => $villa,
            'unitTypes' => $unitTypes,
            'locale' => $locale,
            'scene' => $scene,
            'backdrop' => $this->sceneBackdrop($scene),
            'menuItems' => $this->stageMenu($villa, $locale, $labels, $lobby),
            'supportedLocales' => self::SUPPORTED_LOCALES,
            'labels' => $labels,
            'lobby' => $lobby,
            'unitTerms' => self::UNIT_TERMS[$locale],
            'wizard' => $this->wizardLabels($locale),
            'narration' => self::NARRATION[$locale],
            'unitNarrations' => $this->unitNarrations($villa, $unitTypes, $locale),
            'staffLinks' => $this->handover->forStaff($villa, $locale),
            'today' => now($villa->timezone)->toDateString(),
            'infoItems' => $villa->knowledgeItems()->where('is_active', true)
                ->whereIn('category', ['general', 'policies', 'faq'])
                ->orderBy('sort_order')->get()->groupBy('category'),
            'facilities' => $facilities,
            'sceneNarrations' => $this->sceneNarrations($villa, $facilities, $locale),
        ];
    }

    /**
     * Artwork for a scene. A villa can supply either a single picture with
     * the concierge already in it, or — preferred — a plain background plus
     * one cut-out of her (transparent PNG/WebP) that is layered in front, so
     * she can be placed clear of the panels and reused across scenes.
     *
     * @return array{image: string, focus: string, focusMobile: string, character: ?string, anchor: string, text: string, avatarImage: string, avatarZoom: string, avatarFocus: string}
     */
    private function sceneBackdrop(string $scene): array
    {
        $scenes = [
            'units' => [
                'combined' => 'images/suite.webp',
                'background' => 'images/units-bg.png',
                'focus' => 'center 26%',
                'focusMobile' => '50% 14%',
                'anchor' => 'right',
                'text' => 'top',
                'avatarZoom' => '500%',
                'avatarFocus' => '52% 10%',
            ],
            'facilities' => [
                'combined' => 'images/facility.webp',
                'background' => 'images/facilities-bg.png',
                'focus' => 'center 30%',
                'focusMobile' => '24% 18%',
                // She stands on the left of this artwork, so the scene text
                // sits low and leaves her face clear.
                'anchor' => 'left',
                'text' => 'bottom',
                'avatarZoom' => '500%',
                'avatarFocus' => '17% 11%',
            ],
            'info' => [
                // A plain terrace backdrop with her greeting cut-out layered
                // in front, standing clear on the right of the panel.
                'combined' => 'images/information.webp',
                'background' => 'images/information.webp',
                'character' => 'images/character/character greeting.webp',
                'focus' => 'center 38%',
                'focusMobile' => '70% 30%',
                'anchor' => 'right',
                'text' => 'bottom',
                'avatarZoom' => '300%',
                'avatarFocus' => '50% 25%',
            ],
            'staff' => [
                // A reception lounge backdrop with her grateful cut-out
                // centred, so the panel floats low and leaves her clear.
                'combined' => 'images/talk to staff.webp',
                'background' => 'images/talk to staff.webp',
                'character' => 'images/character/character grateful.webp',
                'focus' => 'center 30%',
                'focusMobile' => '62% 20%',
                'anchor' => 'center',
                'text' => 'top',
                'avatarZoom' => '260%',
                'avatarFocus' => '50% 12%',
            ],
            'reservation' => [
                // She stands on the left holding a tablet with a booking
                // form, echoing the wizard panel that sits beside her.
                'combined' => 'images/reservation.webp',
                'background' => 'images/reservation.webp',
                'character' => 'images/character/character reservation.webp',
                'focus' => 'center 30%',
                'focusMobile' => '24% 18%',
                'anchor' => 'left',
                'text' => 'bottom',
                'avatarZoom' => '300%',
                'avatarFocus' => '42% 14%',
            ],
            'lobby' => [
                'combined' => 'images/concierge-lobby.webp',
                'background' => 'images/lobby-bg.png',
                'focus' => 'center 22%',
                'focusMobile' => '50% 15%',
                'anchor' => 'center',
                'text' => 'top',
                'avatarZoom' => '315%',
                'avatarFocus' => '51% 19%',
            ],
        ];

        $artwork = $scenes[['unit' => 'units', 'facility' => 'facilities'][$scene] ?? $scene] ?? $scenes['lobby'];

        $characterImage = $artwork['character'] ?? self::CHARACTER_IMAGE;
        $character = file_exists(public_path($characterImage)) ? $characterImage : null;
        $background = $character && file_exists(public_path($artwork['background'])) ? $artwork['background'] : null;

        if ($background === null) {
            // No separate background for this scene, so use the picture that
            // already has her in it — and do not layer her on top of herself.
            $character = null;
            $background = file_exists(public_path($artwork['combined'])) ? $artwork['combined'] : $scenes['lobby']['combined'];
        }

        return [
            'image' => asset($background),
            'focus' => $artwork['focus'],
            'focusMobile' => $artwork['focusMobile'],
            'character' => $character ? asset($character) : null,
            'anchor' => $artwork['anchor'],
            'text' => $artwork['text'],
            // The avatars crop her face out of whichever picture holds her —
            // the cut-out when one is layered in, otherwise the background.
            'avatarImage' => asset($character ?? $background),
            'avatarZoom' => $artwork['avatarZoom'],
            'avatarFocus' => $artwork['avatarFocus'],
        ];
    }

    /**
     * The main menu. Sections that live on another page are marked so the
     * stage can fade out, as if the concierge were walking the guest over.
     *
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $lobby
     * @return list<array{key: string, label: string, href: string, exit: bool, tour: ?string, topic: string}>
     */
    private function stageMenu(Villa $villa, string $locale, array $labels, array $lobby): array
    {
        $unitsUrl = route('villa.units', ['villaSlug' => $villa->slug, 'lang' => $locale]);
        $tour = self::NARRATION[$locale];

        $facilitiesUrl = route('villa.facilities', ['villaSlug' => $villa->slug, 'lang' => $locale]);
        $infoUrl = route('villa.info', ['villaSlug' => $villa->slug, 'lang' => $locale]);
        $reservationUrl = route('villa.reservation', ['villaSlug' => $villa->slug, 'lang' => $locale]);
        $staffUrl = route('villa.staff', ['villaSlug' => $villa->slug, 'lang' => $locale]);

        return [
            ['key' => 'units', 'label' => $labels['units_heading'], 'href' => $unitsUrl, 'exit' => true, 'tour' => $tour['tour_units'], 'topic' => $labels['menu_units_q']],
            ['key' => 'facilities', 'label' => $labels['menu_facilities'], 'href' => $facilitiesUrl, 'exit' => true, 'tour' => $tour['tour_facilities'], 'topic' => $labels['menu_facilities_q']],
            ['key' => 'info', 'label' => $lobby['menu_info'], 'href' => $infoUrl, 'exit' => true, 'tour' => $tour['tour_info'], 'topic' => $labels['menu_policies_q']],
            ['key' => 'reservation', 'label' => $lobby['reservation'], 'href' => $reservationUrl, 'exit' => true, 'tour' => $tour['tour_reservation'], 'topic' => $lobby['reservation_q']],
            ['key' => 'staff', 'label' => $labels['menu_staff'], 'href' => $staffUrl, 'exit' => true, 'tour' => $tour['tour_staff'], 'topic' => $labels['menu_staff_q']],
        ];
    }

    /**
     * Intros for the facilities list and the villa information scene, built
     * only from stored villa data.
     *
     * @param  Collection<int, VillaKnowledgeItem>  $facilities
     * @return array{facilities: ?string, info: string}
     */
    private function sceneNarrations(Villa $villa, Collection $facilities, string $locale): array
    {
        $templates = self::NARRATION[$locale];
        $separator = $locale === 'ja' ? '、' : '; ';
        $time = fn (?string $value) => $value ? substr($value, 0, 5) : null;

        $intro = null;

        if ($facilities->isNotEmpty()) {
            $examples = $facilities->take(3)->map(fn ($item) => $item->translatedTitle($locale))->implode($separator);
            $intro = str_replace(
                [':count', ':examples'],
                [(string) $facilities->count(), $examples],
                $templates[$facilities->count() === 1 ? 'facilities_one' : 'facilities']
            );
        }

        $location = collect([$villa->city, $villa->country])->filter()->implode($locale === 'ja' ? '、' : ', ');
        $parts = [str_replace(':villa', $villa->name, $templates['info_welcome'])];

        if ($location !== '') {
            $parts[] = str_replace(':location', $location, $templates['info_place']);
        }

        if ($time($villa->check_in_time) && $time($villa->check_out_time)) {
            $parts[] = str_replace([':in', ':out'], [$time($villa->check_in_time), $time($villa->check_out_time)], $templates['info_hours']);
        }

        $parts[] = $templates['info_more'];

        return ['facilities' => $intro, 'info' => implode(' ', $parts)];
    }

    /**
     * @param  Collection<int, UnitType>  $unitTypes
     * @return array{units: ?string, unit: array<string, string>}
     */
    private function unitNarrations(Villa $villa, Collection $unitTypes, string $locale): array
    {
        $templates = self::NARRATION[$locale];
        $terms = self::UNIT_TERMS[$locale];
        $money = fn ($value) => $villa->currency.' '.number_format((float) $value, 0, ',', '.');
        $sentence = fn (string $text) => preg_match('/[.!?。！？]$/u', $text) ? $text : $text.($locale === 'ja' ? '。' : '.');

        if ($unitTypes->isEmpty()) {
            return ['units' => null, 'unit' => []];
        }

        $intro = str_replace(
            [':count', ':price'],
            [(string) $unitTypes->count(), $money($unitTypes->min('base_price'))],
            $templates[$unitTypes->count() === 1 ? 'units_one' : 'units']
        );

        $units = $unitTypes->mapWithKeys(function ($unitType) use ($templates, $terms, $money, $sentence, $locale) {
            $parts = [$unitType->translatedName($locale).'.', filled($unitType->translatedDescription($locale)) ? $sentence(trim($unitType->translatedDescription($locale))) : null];

            $parts[] = $unitType->size_sqm
                ? str_replace([':size', ':guests'], [(string) $unitType->size_sqm, (string) $unitType->maxOccupancy()], $templates['size_guests'])
                : str_replace(':guests', (string) $unitType->maxOccupancy(), $templates['guests']);

            if ($unitType->view_type) {
                $parts[] = $sentence($terms['view'][$unitType->view_type] ?? Str::headline($unitType->view_type));
            }

            if ($unitType->breakfast_included) {
                $parts[] = $templates['breakfast'];
            }

            $parts[] = str_replace(':price', $money($unitType->base_price), $templates['price']);

            return [$unitType->slug => implode(' ', array_filter($parts))];
        })->all();

        return ['units' => $intro, 'unit' => $units];
    }

    /**
     * @return array<string, string>
     */
    private function openingLabels(string $locale): array
    {
        return match ($locale) {
            'en' => [
                'welcome' => 'Welcome to', 'tagline' => 'Your AI concierge, ready to help you find the perfect stay.',
                'enter' => 'Enter :name', 'empty' => 'The virtual lobby is being prepared. Please come back soon.',
                'loading' => 'Preparing your villa experience…', 'sound_on' => 'Sound on', 'sound_off' => 'Sound off', 'units' => 'Explore villas', 'facilities' => 'See facilities', 'reservation' => 'Plan a reservation', 'staff' => 'Talk to staff',
            ],
            'ja' => [
                'welcome' => 'ようこそ', 'tagline' => 'AIコンシェルジュが、理想のご滞在をお手伝いします。',
                'enter' => ':name に入る', 'empty' => 'バーチャルロビーは準備中です。しばらくしてからお越しください。',
                'loading' => 'ヴィラ体験を準備しています…', 'sound_on' => 'サウンドオン', 'sound_off' => 'サウンドオフ', 'units' => 'ヴィラを見る', 'facilities' => '施設を見る', 'reservation' => '予約を計画する', 'staff' => 'スタッフに相談',
            ],
            default => [
                'welcome' => 'Selamat datang di', 'tagline' => 'AI Concierge siap membantu Anda menemukan pengalaman menginap terbaik.',
                'enter' => 'Masuk ke :name', 'empty' => 'Lobi virtual sedang dipersiapkan. Silakan kembali lagi nanti.',
                'loading' => 'Menyiapkan pengalaman villa Anda…', 'sound_on' => 'Suara aktif', 'sound_off' => 'Suara mati', 'units' => 'Jelajahi villa', 'facilities' => 'Lihat fasilitas', 'reservation' => 'Rencanakan reservasi', 'staff' => 'Bicara dengan staf',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function wizardLabels(string $locale): array
    {
        return [...ReservationController::MESSAGES[$locale], ...match ($locale) {
            'en' => [
                'title' => 'Reservation request', 'intro' => 'A few quick steps. Final availability is confirmed by our team.',
                'step_of' => 'Step :current of :total', 'steps' => ['Dates', 'Guests', 'Villa', 'Your details', 'Summary'],
                'check_in' => 'Check-in', 'check_out' => 'Check-out', 'nights' => 'night(s)', 'adults' => 'Adults', 'children' => 'Children', 'units' => 'Villas',
                'choose_unit' => 'Choose a villa', 'fits' => 'Up to :count guests per villa', 'too_small' => 'Too small for your party',
                'extra_bed' => 'Add an extra bed', 'name' => 'Full name', 'contact_method' => 'How should we contact you?',
                'whatsapp' => 'WhatsApp', 'phone' => 'Phone', 'email' => 'Email', 'contact_value' => 'Number or email address',
                'special' => 'Special request (optional)', 'special_placeholder' => 'e.g. honeymoon setup, late arrival',
                'next' => 'Next', 'back' => 'Back', 'edit' => 'Edit', 'submit' => 'Submit reservation request', 'sending' => 'Sending…',
                'checking' => 'Checking availability…', 'estimated_total' => 'Estimated total', 'guests' => 'Guests', 'unit' => 'Villa', 'dates' => 'Dates',
                'contact' => 'Contact', 'disclaimer' => 'This is a reservation request. Final availability and rates are confirmed by villa staff. No payment is taken now.',
                'available_instead' => 'Available for your dates instead:', 'done_title' => 'Request received', 'reference' => 'Your reference',
                'awaiting' => 'Awaiting villa confirmation', 'done_hint' => 'Send your request to our team to speed up confirmation.',
                'send_whatsapp' => 'Send to WhatsApp', 'call_villa' => 'Call the villa', 'email_villa' => 'Email the villa', 'new_request' => 'New request',
                'error_network' => 'Connection problem. Your details are saved — please try again or contact villa staff.',
                'error_generic' => 'Something went wrong. Please try again or contact villa staff.', 'select_unit' => 'Please choose a villa.',
                'contact_staff' => 'Contact villa staff',
            ],
            'ja' => [
                'title' => 'ご予約リクエスト', 'intro' => 'かんたんな手順です。空き状況はスタッフが最終確認いたします。',
                'step_of' => 'ステップ :current / :total', 'steps' => ['日程', '人数', 'ヴィラ', 'ご連絡先', '確認'],
                'check_in' => 'チェックイン', 'check_out' => 'チェックアウト', 'nights' => '泊', 'adults' => '大人', 'children' => '子ども', 'units' => 'ヴィラ数',
                'choose_unit' => 'ヴィラを選ぶ', 'fits' => '1棟あたり最大:count名', 'too_small' => '人数に対応できません',
                'extra_bed' => 'エキストラベッドを追加', 'name' => 'お名前', 'contact_method' => 'ご連絡方法',
                'whatsapp' => 'WhatsApp', 'phone' => '電話', 'email' => 'メール', 'contact_value' => '番号またはメールアドレス',
                'special' => 'ご要望（任意）', 'special_placeholder' => '例：ハネムーンの飾り付け、遅い到着',
                'next' => '次へ', 'back' => '戻る', 'edit' => '編集', 'submit' => '予約リクエストを送信', 'sending' => '送信中…',
                'checking' => '空きを確認中…', 'estimated_total' => '概算合計', 'guests' => '人数', 'unit' => 'ヴィラ', 'dates' => '日程',
                'contact' => 'ご連絡先', 'disclaimer' => 'これは予約リクエストです。空き状況と料金はスタッフが最終確認します。現時点でお支払いは発生しません。',
                'available_instead' => 'ご希望の日程で空いているヴィラ：', 'done_title' => 'リクエストを受け付けました', 'reference' => '予約番号',
                'awaiting' => 'ヴィラの確認待ち', 'done_hint' => 'リクエストをスタッフに送ると、確認がスムーズです。',
                'send_whatsapp' => 'WhatsAppで送る', 'call_villa' => 'ヴィラに電話', 'email_villa' => 'ヴィラにメール', 'new_request' => '新しいリクエスト',
                'error_network' => '接続に問題があります。入力内容は保存されています。もう一度お試しいただくか、スタッフにご連絡ください。',
                'error_generic' => 'エラーが発生しました。もう一度お試しいただくか、スタッフにご連絡ください。', 'select_unit' => 'ヴィラを選択してください。',
                'contact_staff' => 'スタッフに連絡',
            ],
            default => [
                'title' => 'Permintaan reservasi', 'intro' => 'Hanya beberapa langkah singkat. Ketersediaan final dikonfirmasi oleh tim kami.',
                'step_of' => 'Langkah :current dari :total', 'steps' => ['Tanggal', 'Tamu', 'Villa', 'Data Anda', 'Ringkasan'],
                'check_in' => 'Check-in', 'check_out' => 'Check-out', 'nights' => 'malam', 'adults' => 'Dewasa', 'children' => 'Anak', 'units' => 'Jumlah villa',
                'choose_unit' => 'Pilih villa', 'fits' => 'Maks. :count tamu per villa', 'too_small' => 'Tidak cukup untuk rombongan Anda',
                'extra_bed' => 'Tambah extra bed', 'name' => 'Nama lengkap', 'contact_method' => 'Bagaimana kami menghubungi Anda?',
                'whatsapp' => 'WhatsApp', 'phone' => 'Telepon', 'email' => 'Email', 'contact_value' => 'Nomor atau alamat email',
                'special' => 'Permintaan khusus (opsional)', 'special_placeholder' => 'mis. dekorasi bulan madu, tiba larut malam',
                'next' => 'Lanjut', 'back' => 'Kembali', 'edit' => 'Ubah', 'submit' => 'Kirim permintaan reservasi', 'sending' => 'Mengirim…',
                'checking' => 'Memeriksa ketersediaan…', 'estimated_total' => 'Perkiraan total', 'guests' => 'Tamu', 'unit' => 'Villa', 'dates' => 'Tanggal',
                'contact' => 'Kontak', 'disclaimer' => 'Ini adalah permintaan reservasi. Ketersediaan dan tarif final dikonfirmasi oleh staf villa. Belum ada pembayaran yang diambil.',
                'available_instead' => 'Tersedia di tanggal Anda:', 'done_title' => 'Permintaan diterima', 'reference' => 'Nomor referensi',
                'awaiting' => 'Menunggu konfirmasi villa', 'done_hint' => 'Kirim permintaan ke tim kami agar konfirmasi lebih cepat.',
                'send_whatsapp' => 'Kirim ke WhatsApp', 'call_villa' => 'Telepon villa', 'email_villa' => 'Email villa', 'new_request' => 'Permintaan baru',
                'error_network' => 'Koneksi bermasalah. Data Anda tersimpan — coba lagi atau hubungi staf villa.',
                'error_generic' => 'Terjadi kesalahan. Coba lagi atau hubungi staf villa.', 'select_unit' => 'Silakan pilih villa.',
                'contact_staff' => 'Hubungi staf villa',
            ],
        }];
    }

    private function lobbyLabels(string $locale): array
    {
        return match ($locale) {
            'en' => [
                'welcome' => 'Welcome to your', 'lobby' => 'virtual lobby',
                'intro' => 'A warm welcome. A wonderful stay. Let me take care of the details.',
                'home' => 'Lobby', 'explore' => 'Explore your stay', 'reservation' => 'Reservations',
                'reservation_intro' => 'Tell your AI Concierge your dates and number of guests. We will help you find a villa and submit a booking request.',
                'reservation_q' => 'I would like to book a villa. Please help me check availability.',
                'start_booking' => 'Plan my stay', 'available' => 'Here to help, 24/7',
                'assistant' => 'Your virtual host', 'illustration' => 'AI illustration',
                'staff_intro' => 'Need a personal touch? Send a message and we will connect you with the villa team.',
                'empty' => 'Ask your AI Concierge for more information.', 'back' => 'Back to lobby',
                'check_in' => 'Check-in', 'check_out' => 'Check-out', 'location' => 'Find us',
                'connection_error' => 'Chat could not connect. Please reload to try again.',
                'units_empty' => 'Villa information will be available soon. Please ask our team.',
                'menu_info' => 'Villa information', 'info_about' => 'About the villa', 'info_policies' => 'Policies', 'info_faq' => 'Frequently asked questions', 'info_tab_about' => 'About', 'info_tab_faq' => 'FAQ',
                'info_address' => 'Address', 'info_hours' => 'Check-in / check-out', 'info_contact' => 'Contact', 'info_map' => 'Open in Maps', 'info_ask' => 'Ask about the villa',
                'facility_counter' => 'Facility', 'prev_facility' => 'Previous', 'next_facility' => 'Next', 'ask_facility' => 'Ask about this facility', 'back_facilities' => 'All facilities', 'open_facility' => 'Read more', 'ask_facility_q' => 'Tell me more about :name.',
                'loading' => 'Preparing your villa experience…',
                'sound_on' => 'Sound on', 'sound_off' => 'Sound off',
                'unit_scene' => 'Villa details', 'unit_counter' => 'Villa', 'gallery' => 'Photo gallery', 'photo' => 'Photo',
                'no_photo' => 'Photos coming soon', 'prev_unit' => 'Previous villa', 'next_unit' => 'Next villa',
                'ask_unit' => 'Ask about this villa', 'reserve_unit' => 'Request reservation', 'breakfast_excluded' => 'Stay only', 'breakfast' => 'Breakfast',
                'size' => 'Size', 'bed' => 'Bed', 'guests' => 'Guests', 'view' => 'View', 'extra_bed' => 'Extra bed',
                'extra_bed_yes' => 'Available', 'extra_bed_no' => 'Not available', 'amenities' => 'In the villa',
                'availability_note' => 'Final availability and rates are confirmed by villa staff.',
                'ask_unit_q' => 'Tell me more about the :name.',
            ],
            'ja' => [
                'welcome' => 'ようこそ', 'lobby' => 'バーチャルロビー',
                'intro' => '心を込めたおもてなしで、素敵なご滞在をお手伝いします。',
                'home' => 'ロビー', 'explore' => 'ご滞在のご案内', 'reservation' => 'ご予約',
                'reservation_intro' => 'ご希望の日程と人数をAIコンシェルジュにお伝えください。空き確認と予約リクエストをお手伝いします。',
                'reservation_q' => 'ヴィラを予約したいです。空きを確認してください。',
                'start_booking' => '滞在を計画する', 'available' => '24時間お手伝いします',
                'assistant' => 'バーチャルホスト', 'illustration' => 'AIイラスト',
                'staff_intro' => 'ヴィラのスタッフがお手伝いします。メッセージを送ってご相談ください。',
                'empty' => '詳しくはAIコンシェルジュにお尋ねください。', 'back' => 'ロビーに戻る',
                'check_in' => 'チェックイン', 'check_out' => 'チェックアウト', 'location' => 'アクセス',
                'connection_error' => 'チャットに接続できませんでした。再読み込みしてください。',
                'units_empty' => 'ヴィラ情報は準備中です。スタッフにお尋ねください。',
                'menu_info' => 'ヴィラ情報', 'info_about' => 'ヴィラについて', 'info_policies' => 'ご利用規定', 'info_faq' => 'よくあるご質問', 'info_tab_about' => 'ヴィラについて', 'info_tab_faq' => 'FAQ',
                'info_address' => '所在地', 'info_hours' => 'チェックイン / チェックアウト', 'info_contact' => 'お問い合わせ', 'info_map' => '地図で開く', 'info_ask' => 'ヴィラについて聞く',
                'facility_counter' => '施設', 'prev_facility' => '前へ', 'next_facility' => '次へ', 'ask_facility' => 'この施設について聞く', 'back_facilities' => '施設一覧', 'open_facility' => '詳しく見る', 'ask_facility_q' => ':name について詳しく教えてください。',
                'loading' => 'ヴィラ体験を準備しています…',
                'sound_on' => 'サウンドオン', 'sound_off' => 'サウンドオフ',
                'unit_scene' => 'ヴィラのご案内', 'unit_counter' => 'ヴィラ', 'gallery' => 'フォトギャラリー', 'photo' => '写真',
                'no_photo' => '写真は準備中です', 'prev_unit' => '前のヴィラ', 'next_unit' => '次のヴィラ',
                'ask_unit' => 'このヴィラについて聞く', 'reserve_unit' => '予約をリクエスト', 'breakfast_excluded' => '朝食なし', 'breakfast' => '朝食',
                'size' => '広さ', 'bed' => 'ベッド', 'guests' => '定員', 'view' => '眺望', 'extra_bed' => 'エキストラベッド',
                'extra_bed_yes' => '利用可', 'extra_bed_no' => '利用不可', 'amenities' => 'ヴィラ設備',
                'availability_note' => '空き状況と料金は、ヴィラスタッフが最終確認いたします。',
                'ask_unit_q' => ':name について詳しく教えてください。',
            ],
            default => [
                'welcome' => 'Selamat datang di', 'lobby' => 'lobi virtual Anda',
                'intro' => 'Sambutan hangat. Pengalaman istimewa. Biarkan saya membantu rencana menginap Anda.',
                'home' => 'Beranda', 'explore' => 'Jelajahi villa', 'reservation' => 'Reservasi',
                'reservation_intro' => 'Ceritakan tanggal menginap dan jumlah tamu kepada AI Concierge. Kami bantu cek ketersediaan villa hingga pengajuan reservasi.',
                'reservation_q' => 'Saya ingin reservasi villa. Bantu saya cek ketersediaan.',
                'start_booking' => 'Rencanakan menginap', 'available' => 'Siap membantu, 24 jam',
                'assistant' => 'Resepsionis virtual Anda', 'illustration' => 'Ilustrasi AI',
                'staff_intro' => 'Butuh bantuan langsung? Kirim pesan untuk terhubung dengan tim villa kami.',
                'empty' => 'Tanyakan informasi selengkapnya kepada AI Concierge.', 'back' => 'Kembali ke lobi',
                'check_in' => 'Check-in', 'check_out' => 'Check-out', 'location' => 'Lokasi villa',
                'connection_error' => 'Chat belum tersambung. Muat ulang halaman untuk mencoba lagi.',
                'units_empty' => 'Informasi villa segera tersedia. Silakan tanyakan kepada staf kami.',
                'menu_info' => 'Informasi villa', 'info_about' => 'Tentang villa', 'info_policies' => 'Kebijakan', 'info_faq' => 'Pertanyaan yang sering diajukan', 'info_tab_about' => 'Tentang', 'info_tab_faq' => 'FAQ',
                'info_address' => 'Alamat', 'info_hours' => 'Check-in / check-out', 'info_contact' => 'Kontak', 'info_map' => 'Buka di Maps', 'info_ask' => 'Tanya tentang villa',
                'facility_counter' => 'Fasilitas', 'prev_facility' => 'Sebelumnya', 'next_facility' => 'Berikutnya', 'ask_facility' => 'Tanya tentang fasilitas ini', 'back_facilities' => 'Semua fasilitas', 'open_facility' => 'Selengkapnya', 'ask_facility_q' => 'Ceritakan lebih banyak tentang :name.',
                'loading' => 'Menyiapkan pengalaman villa Anda…',
                'sound_on' => 'Suara aktif', 'sound_off' => 'Suara mati',
                'unit_scene' => 'Detail villa', 'unit_counter' => 'Villa', 'gallery' => 'Galeri foto', 'photo' => 'Foto',
                'no_photo' => 'Foto segera tersedia', 'prev_unit' => 'Villa sebelumnya', 'next_unit' => 'Villa berikutnya',
                'ask_unit' => 'Tanya tentang villa ini', 'reserve_unit' => 'Ajukan reservasi', 'breakfast_excluded' => 'Tanpa sarapan', 'breakfast' => 'Sarapan',
                'size' => 'Ukuran', 'bed' => 'Tempat tidur', 'guests' => 'Kapasitas', 'view' => 'Pemandangan', 'extra_bed' => 'Extra bed',
                'extra_bed_yes' => 'Tersedia', 'extra_bed_no' => 'Tidak tersedia', 'amenities' => 'Fasilitas villa',
                'availability_note' => 'Ketersediaan dan tarif final dikonfirmasi oleh staf villa.',
                'ask_unit_q' => 'Ceritakan lebih banyak tentang :name.',
            ],
        };
    }

    private function labels(string $locale): array
    {
        return match ($locale) {
            'en' => [
                'from' => 'from',
                'per_night' => '/ night',
                'ask_ai' => 'Ask the AI Concierge',
                'units_heading' => 'Our Villas',
                'chat_heading' => 'AI Concierge',
                'chat_subtitle' => 'Ask about villas, facilities, or your stay — available 24/7.',
                'chat_placeholder' => 'Type your message…',
                'chat_send' => 'Send',
                'chat_open' => 'Chat with AI Concierge',
                'chat_close' => 'Close conversation',
                'chat_intro' => "Hi! I'm the AI Concierge here. Ask me about villas, facilities, or anything about your stay.",
                'breakfast_included' => 'Breakfast included',
                'max_guests' => 'guests',
                'chat_unit_only' => 'Stay only',
                'chat_night' => 'night(s)',
                'chat_no_availability' => 'No availability for those dates.',
                'chat_booking_received' => 'Booking request received',
                'chat_reference' => 'Ref',
                'chat_view_suffix' => 'view',
                'handed_over' => 'A staff member has joined this conversation and will reply shortly.',
                'chat_status_sent' => 'Message sent',
                'chat_status_waiting' => 'Waiting for staff reply',
                'chat_status_replied' => 'Staff has replied',
                'chat_draft_title' => 'Unsaved message',
                'chat_draft_body' => 'This message has not been sent. Keep it as a draft?',
                'chat_draft_keep' => 'Keep typing',
                'chat_draft_discard' => 'Discard draft',
                'thinking' => 'Thinking…',
                'chat_error' => "I'm having trouble responding right now. Please try again or contact villa staff.", 'chat_slow' => 'You are sending messages very quickly. Please wait a moment and try again.', 'chat_retry' => 'Retry',
                'view_details' => 'View details',
                'unit_details_question' => 'Show me more details and photos of :unit',
                'book_now' => 'Book this villa',
                'menu_heading' => 'Start here',
                'menu_units' => 'Find a villa',
                'menu_units_q' => 'I would like to see the available villas.',
                'menu_facilities' => 'Villa facilities',
                'menu_facilities_q' => 'What facilities does the villa have?',
                'menu_policies' => 'Policies & FAQ',
                'menu_policies_q' => 'What are the check-in, check-out, and cancellation policies?',
                'menu_staff' => 'Talk to staff',
                'menu_staff_q' => 'I would like to speak with villa staff.',
            ],
            'ja' => [
                'from' => '',
                'per_night' => '〜 / 泊',
                'ask_ai' => 'AIコンシェルジュに聞く',
                'units_heading' => 'ヴィラ一覧',
                'chat_heading' => 'AIコンシェルジュ',
                'chat_subtitle' => 'ヴィラ・設備・ご滞在について24時間いつでもお尋ねください。',
                'chat_placeholder' => 'メッセージを入力…',
                'chat_send' => '送信',
                'chat_open' => 'AIコンシェルジュに相談',
                'chat_close' => '会話を閉じる',
                'chat_intro' => 'こんにちは。AIコンシェルジュです。ヴィラや設備、ご滞在について何でもお尋ねください。',
                'breakfast_included' => '朝食付き',
                'max_guests' => '名まで',
                'chat_unit_only' => '食事なし',
                'chat_night' => '泊',
                'chat_no_availability' => 'ご希望の日程には空きがありません。',
                'chat_booking_received' => '予約リクエストを受け付けました',
                'chat_reference' => '受付番号',
                'chat_view_suffix' => 'の眺望',
                'handed_over' => 'スタッフがこの会話に参加しました。まもなく返信いたします。',
                'chat_status_sent' => 'メッセージを送信しました',
                'chat_status_waiting' => 'スタッフの返信を待っています',
                'chat_status_replied' => 'スタッフが返信しました',
                'chat_draft_title' => '未送信のメッセージ',
                'chat_draft_body' => 'このメッセージはまだ送信されていません。下書きとして残しますか？',
                'chat_draft_keep' => '入力を続ける',
                'chat_draft_discard' => '下書きを破棄',
                'thinking' => '入力中…',
                'chat_error' => '現在うまくお答えできません。もう一度お試しいただくか、スタッフにご連絡ください。', 'chat_slow' => 'メッセージが多すぎます。少し待ってからもう一度お試しください。', 'chat_retry' => '再試行',
                'view_details' => '詳細を見る',
                'unit_details_question' => ':unit の詳細と写真を見せてください',
                'book_now' => 'このヴィラを予約',
                'menu_heading' => 'ここから始める',
                'menu_units' => 'ヴィラを探す',
                'menu_units_q' => '空いているヴィラを見せてください。',
                'menu_facilities' => 'ヴィラ施設',
                'menu_facilities_q' => 'ヴィラにはどんな施設がありますか？',
                'menu_policies' => '規定・よくある質問',
                'menu_policies_q' => 'チェックイン、チェックアウト、キャンセルのポリシーを教えてください。',
                'menu_staff' => 'スタッフに相談',
                'menu_staff_q' => 'ヴィラスタッフと話したいです。',
            ],
            default => [
                'from' => 'mulai dari',
                'per_night' => '/ malam',
                'ask_ai' => 'Tanya AI Concierge',
                'units_heading' => 'Pilihan Villa',
                'chat_heading' => 'AI Concierge',
                'chat_subtitle' => 'Tanyakan villa, fasilitas, atau rencana menginap Anda — siap 24 jam.',
                'chat_placeholder' => 'Tulis pesan Anda…',
                'chat_send' => 'Kirim',
                'chat_open' => 'Chat dengan AI Concierge',
                'chat_close' => 'Tutup percakapan',
                'chat_intro' => 'Halo! Saya AI Concierge villa ini. Tanyakan apa saja soal villa, fasilitas, atau rencana menginap Anda.',
                'breakfast_included' => 'Termasuk sarapan',
                'max_guests' => 'tamu',
                'chat_unit_only' => 'Tanpa sarapan',
                'chat_night' => 'malam',
                'chat_no_availability' => 'Tidak ada villa tersedia untuk tanggal tersebut.',
                'chat_booking_received' => 'Permintaan reservasi diterima',
                'chat_reference' => 'Ref',
                'chat_view_suffix' => 'pemandangan',
                'handed_over' => 'Staf kami telah bergabung dalam percakapan ini dan akan segera membalas.',
                'chat_status_sent' => 'Pesan terkirim',
                'chat_status_waiting' => 'Menunggu balasan staf',
                'chat_status_replied' => 'Staf telah membalas',
                'chat_draft_title' => 'Pesan belum dikirim',
                'chat_draft_body' => 'Pesan ini belum dikirim. Simpan sebagai draft?',
                'chat_draft_keep' => 'Lanjut mengetik',
                'chat_draft_discard' => 'Buang draft',
                'thinking' => 'Sedang mengetik…',
                'chat_error' => 'Saya sedang kesulitan menjawab. Silakan coba lagi atau hubungi staf villa.', 'chat_slow' => 'Pesan terlalu cepat. Mohon tunggu sebentar lalu coba lagi.', 'chat_retry' => 'Coba lagi',
                'view_details' => 'Lihat detail',
                'unit_details_question' => 'Tunjukkan detail dan foto lengkap :unit',
                'book_now' => 'Pesan villa ini',
                'menu_heading' => 'Mulai dari sini',
                'menu_units' => 'Cari villa',
                'menu_units_q' => 'Saya ingin melihat pilihan villa yang tersedia.',
                'menu_facilities' => 'Fasilitas villa',
                'menu_facilities_q' => 'Apa saja fasilitas yang tersedia di villa ini?',
                'menu_policies' => 'Kebijakan & FAQ',
                'menu_policies_q' => 'Apa kebijakan check-in, check-out, dan pembatalan?',
                'menu_staff' => 'Bicara dengan staf',
                'menu_staff_q' => 'Saya ingin bicara dengan staf villa.',
            ],
        };
    }
}
