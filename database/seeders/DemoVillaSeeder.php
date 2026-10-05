<?php

namespace Database\Seeders;

use App\Models\UnitInventory;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Villa;
use App\Models\VillaKnowledgeItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoVillaSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@ftsvilla.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $villa = Villa::firstOrNew(['name' => 'FTS Villa AI']);
        $villa->fill([
            'slug' => $villa->slug ?? Villa::generateUniqueSlug('FTS Villa AI'),
            'description' => 'Kompleks villa privat di kawasan Puncak, Bogor, dikelilingi kebun teh dan udara pegunungan yang sejuk, dengan pemandangan Gunung Gede-Pangrango.',
            'translations' => [
                'id' => [
                    'description' => 'Kompleks villa privat di kawasan Puncak, Bogor, dikelilingi kebun teh dan udara pegunungan yang sejuk, dengan pemandangan Gunung Gede-Pangrango.',
                ],
                'en' => [
                    'description' => 'A collection of private villas in the Puncak highlands of Bogor, surrounded by tea plantations and cool mountain air, with views of Mount Gede-Pangrango.',
                ],
                'ja' => [
                    'description' => 'ボゴールのプンチャック高原に佇むプライベートヴィラリゾート。茶畑と涼しい山の空気に囲まれ、ゲデ・パンランゴ山を望みます。',
                ],
            ],
            'address' => 'Jl. Raya Puncak KM 82, Tugu Utara, Cisarua',
            'city' => 'Bogor',
            'country' => 'Indonesia',
            'latitude' => -6.6957,
            'longitude' => 106.9690,
            'phone' => '+62 251 8254321',
            'whatsapp' => '6281234567890',
            'email' => 'reservation@ftsvilla.test',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'default_locale' => 'id',
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cover_path' => 'https://images.unsplash.com/photo-1582610116397-edb318620f90?auto=format&fit=crop&w=1920&q=80',
            'public_status' => 'published',
        ])->save();

        $villa->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);

        $unitTypes = $this->unitTypeDefinitions();

        $sort = 0;
        foreach ($unitTypes as $definition) {
            $images = $definition['images'];
            $totalUnits = $definition['total_units'];
            unset($definition['images'], $definition['total_units']);

            $unitType = $villa->unitTypes()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );

            $imageSort = 0;
            foreach ($images as $image) {
                $unitType->images()->updateOrCreate(
                    ['image_url' => $image['url']],
                    [
                        'tags' => $image['tags'],
                        'alt_text' => $image['alt'],
                        'sort_order' => $imageSort++,
                    ]
                );
            }

            $this->seedInventory($unitType, $totalUnits);
        }

        $this->seedKnowledgeBase($villa);

        $this->command?->info('Demo login: owner@ftsvilla.test — password: password');
    }

    private function unitTypeDefinitions(): array
    {
        return [
            [
                'slug' => 'one-bedroom-garden-pool-villa',
                'name' => 'One Bedroom Garden Pool Villa',
                'description' => 'Villa satu kamar tidur dengan kolam renang air hangat pribadi dan taman yang asri, cocok untuk pasangan atau keluarga kecil.',
                'translations' => [
                    'id' => ['name' => 'One Bedroom Garden Pool Villa', 'description' => 'Villa satu kamar tidur dengan kolam renang air hangat pribadi dan taman yang asri, cocok untuk pasangan atau keluarga kecil.'],
                    'en' => ['name' => 'One Bedroom Garden Pool Villa', 'description' => 'A one-bedroom villa with its own heated private pool and a leafy garden, ideal for couples or small families.'],
                    'ja' => ['name' => 'ワンベッドルーム ガーデンプールヴィラ', 'description' => '温水のプライベートプールと緑豊かな庭を備えたワンベッドルームのヴィラで、カップルや小さなご家族に最適です。'],
                ],
                'size_sqm' => 120,
                'max_adults' => 2,
                'max_children' => 1,
                'bed_config' => [['type' => 'king', 'count' => 1]],
                'view_type' => 'garden',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 300000,
                'base_price' => 1800000,
                'amenities' => ['private_pool', 'water_heater', 'wifi', 'minibar', 'safe_deposit_box', 'gazebo', 'coffee_maker'],
                'total_units' => 8,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1582610116397-edb318620f90?auto=format&fit=crop&w=1200&q=80', 'tags' => ['pool', 'view'], 'alt' => 'Kolam renang pribadi Garden Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur king Garden Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom'], 'alt' => 'Kamar mandi Garden Pool Villa'],
                ],
            ],
            [
                'slug' => 'one-bedroom-mountain-view-villa',
                'name' => 'One Bedroom Mountain View Villa',
                'description' => 'Villa satu kamar tidur dengan kolam renang air hangat pribadi menghadap Gunung Gede-Pangrango dan bathtub untuk berendam di udara sejuk.',
                'translations' => [
                    'id' => ['name' => 'One Bedroom Mountain View Villa', 'description' => 'Villa satu kamar tidur dengan kolam renang air hangat pribadi menghadap Gunung Gede-Pangrango dan bathtub untuk berendam di udara sejuk.'],
                    'en' => ['name' => 'One Bedroom Mountain View Villa', 'description' => 'A one-bedroom villa with a heated private pool facing Mount Gede-Pangrango and a bathtub for a warm soak in the cool air.'],
                    'ja' => ['name' => 'ワンベッドルーム マウンテンビューヴィラ', 'description' => 'ゲデ・パンランゴ山を望む温水プライベートプールと、涼しい空気の中でくつろげるバスタブを備えたワンベッドルームのヴィラです。'],
                ],
                'size_sqm' => 150,
                'max_adults' => 2,
                'max_children' => 1,
                'bed_config' => [['type' => 'king', 'count' => 1]],
                'view_type' => 'mountain',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 300000,
                'base_price' => 2600000,
                'amenities' => ['private_pool', 'water_heater', 'wifi', 'minibar', 'safe_deposit_box', 'bathtub', 'fireplace', 'coffee_maker'],
                'total_units' => 6,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80', 'tags' => ['pool', 'view'], 'alt' => 'Kolam pribadi menghadap pegunungan'],
                    ['url' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur Mountain View Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom', 'bathtub'], 'alt' => 'Kamar mandi dengan bathtub'],
                ],
            ],
            [
                'slug' => 'two-bedroom-family-pool-villa',
                'name' => 'Two Bedroom Family Pool Villa',
                'description' => 'Villa dua kamar tidur dengan ruang keluarga berperapian, dapur lengkap, area BBQ, dan kolam renang air hangat pribadi — pas untuk liburan keluarga di Puncak.',
                'translations' => [
                    'id' => ['name' => 'Two Bedroom Family Pool Villa', 'description' => 'Villa dua kamar tidur dengan ruang keluarga berperapian, dapur lengkap, area BBQ, dan kolam renang air hangat pribadi — pas untuk liburan keluarga di Puncak.'],
                    'en' => ['name' => 'Two Bedroom Family Pool Villa', 'description' => 'A two-bedroom villa with a living room and fireplace, a full kitchen, a BBQ area and a heated private pool — made for family getaways in Puncak.'],
                    'ja' => ['name' => 'ツーベッドルーム ファミリープールヴィラ', 'description' => '暖炉付きのリビング、フルキッチン、BBQエリア、温水プライベートプールを備えたツーベッドルームのヴィラで、プンチャックでの家族旅行にぴったりです。'],
                ],
                'size_sqm' => 260,
                'max_adults' => 4,
                'max_children' => 2,
                'bed_config' => [['type' => 'king', 'count' => 1], ['type' => 'twin', 'count' => 2]],
                'view_type' => 'garden',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 350000,
                'base_price' => 4200000,
                'amenities' => ['private_pool', 'living_room', 'fireplace', 'kitchen', 'bbq_area', 'water_heater', 'wifi', 'bathtub'],
                'total_units' => 4,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1591088398332-8a7791972843?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur utama Family Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80', 'tags' => ['living_room'], 'alt' => 'Ruang keluarga Family Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom', 'bathtub'], 'alt' => 'Kamar mandi Family Pool Villa'],
                ],
            ],
            [
                'slug' => 'three-bedroom-grand-pool-villa',
                'name' => 'Three Bedroom Grand Pool Villa',
                'description' => 'Villa tiga kamar tidur terluas kami dengan kolam infinity air hangat menghadap lembah dan Gunung Gede-Pangrango, perapian, dapur lengkap, area BBQ, dan layanan butler — cocok untuk rombongan atau keluarga besar.',
                'translations' => [
                    'id' => ['name' => 'Three Bedroom Grand Pool Villa', 'description' => 'Villa tiga kamar tidur terluas kami dengan kolam infinity air hangat menghadap lembah dan Gunung Gede-Pangrango, perapian, dapur lengkap, area BBQ, dan layanan butler — cocok untuk rombongan atau keluarga besar.'],
                    'en' => ['name' => 'Three Bedroom Grand Pool Villa', 'description' => 'Our largest villa, with three bedrooms, a heated infinity pool overlooking the valley and Mount Gede-Pangrango, a fireplace, a full kitchen, a BBQ area and butler service — made for groups and larger families.'],
                    'ja' => ['name' => 'スリーベッドルーム グランドプールヴィラ', 'description' => '渓谷とゲデ・パンランゴ山を望む温水インフィニティプール、暖炉、フルキッチン、BBQエリア、バトラーサービスを備えた最も広いスリーベッドルームのヴィラで、グループや大人数のご家族に最適です。'],
                ],
                'size_sqm' => 400,
                'max_adults' => 6,
                'max_children' => 2,
                'bed_config' => [['type' => 'king', 'count' => 2], ['type' => 'twin', 'count' => 2]],
                'view_type' => 'mountain',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 350000,
                'base_price' => 6500000,
                'amenities' => ['private_pool', 'living_room', 'fireplace', 'kitchen', 'bbq_area', 'butler_service', 'water_heater', 'wifi', 'bathtub'],
                'total_units' => 2,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1518495973542-4542c06a5843?auto=format&fit=crop&w=1200&q=80', 'tags' => ['view'], 'alt' => 'Pemandangan lembah dari Grand Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur Grand Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80', 'tags' => ['living_room'], 'alt' => 'Ruang keluarga Grand Pool Villa'],
                ],
            ],
        ];
    }

    private function seedInventory(UnitType $unitType, int $totalUnits): void
    {
        $start = now()->startOfDay();

        for ($i = 0; $i < 120; $i++) {
            $date = $start->copy()->addDays($i);
            $isWeekend = $date->isFriday() || $date->isSaturday();
            $price = $isWeekend
                ? (float) $unitType->base_price * 1.15
                : (float) $unitType->base_price;

            // Deterministic pseudo-occupancy so the demo shows realistic
            // partial availability instead of every date being wide open.
            $booked = ($i * 7 + $unitType->id * 3) % ($totalUnits + 2);
            $booked = min($booked, $totalUnits);

            // Match on the same value the date cast stores, so re-seeding
            // updates the existing night instead of colliding with it.
            UnitInventory::updateOrCreate(
                ['unit_type_id' => $unitType->id, 'stay_date' => $date],
                [
                    'total_units' => $totalUnits,
                    'booked_units' => $booked,
                    'price' => round($price, -3),
                ]
            );
        }
    }

    private function seedKnowledgeBase(Villa $villa): void
    {
        $items = [
            [
                'category' => VillaKnowledgeItem::CATEGORY_GENERAL,
                'title' => 'Tentang FTS Villa AI',
                'body' => 'FTS Villa AI adalah kompleks 20 villa privat di kawasan Puncak, Cisarua, Bogor, terdiri dari empat tipe villa satu hingga tiga kamar tidur. Setiap villa memiliki kolam renang air hangat pribadi, dikelilingi kebun teh dengan udara pegunungan yang sejuk.',
                'translations' => [
                    'en' => ['title' => 'About FTS Villa AI', 'body' => 'FTS Villa AI is a collection of 20 private villas in the Puncak highlands of Cisarua, Bogor, in four villa types from one to three bedrooms. Every villa has its own heated private pool, surrounded by tea plantations and cool mountain air.'],
                    'ja' => ['title' => 'FTS Villa AIについて', 'body' => 'FTS Villa AIはボゴール・チサルアのプンチャック高原にある20棟のプライベートヴィラで、ワンベッドルームからスリーベッドルームまで4タイプをご用意しています。全ヴィラに温水プライベートプールがあり、茶畑と涼しい山の空気に囲まれています。'],
                ],
                'tags' => ['overview', 'introduction', 'puncak', 'bogor'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_POLICIES,
                'title' => 'Jam check-in dan check-out',
                'body' => 'Check-in standar mulai pukul 14:00 dan check-out pukul 12:00. Early check-in dan late check-out dapat diajukan dan bergantung pada ketersediaan villa; silakan konfirmasi ke staf kami H-1 sebelum kedatangan.',
                'translations' => [
                    'en' => ['title' => 'Check-in and check-out times', 'body' => 'Standard check-in is from 2:00 PM and check-out is at 12:00 PM. Early check-in and late check-out are subject to villa availability; please confirm with our staff one day before arrival.'],
                    'ja' => ['title' => 'チェックイン・チェックアウト時間', 'body' => '標準チェックインは14:00から、チェックアウトは12:00です。アーリーチェックインとレイトチェックアウトは空き状況により対応可能です。到着前日にスタッフまでご確認ください。'],
                ],
                'tags' => ['check-in', 'check-out', 'early check-in', 'late check-out'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_POLICIES,
                'title' => 'Perjalanan ke Puncak dan check-in malam',
                'body' => 'Jalur Puncak memberlakukan sistem satu arah pada akhir pekan dan hari libur, jadi mohon perhitungkan waktu tempuh lebih lama. Resepsionis villa kami buka 24 jam, sehingga Anda tetap bisa check-in kapan saja; staf kami akan mengantar Anda ke villa. Mohon informasikan perkiraan waktu tiba.',
                'translations' => [
                    'en' => ['title' => 'Getting to Puncak and late check-in', 'body' => 'The Puncak road runs a one-way traffic system on weekends and public holidays, so please allow extra travel time. Our villa reception is open 24 hours, so you can still check in at any time, and our staff will walk you to your villa. Please let us know your estimated arrival time.'],
                    'ja' => ['title' => 'プンチャックへの道のりと深夜チェックイン', 'body' => 'プンチャック街道では週末・祝日に一方通行規制が実施されるため、移動時間に余裕をもってお越しください。レセプションは24時間対応しておりますので、いつでもチェックインいただけます。スタッフがヴィラまでご案内します。到着予定時刻を事前にお知らせください。'],
                ],
                'tags' => ['late check-in', 'traffic', 'one way', 'satu arah', 'front desk'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_POLICIES,
                'title' => 'Cuaca dan suhu di Puncak',
                'body' => 'Suhu di area villa sekitar 18–24°C pada siang hari dan bisa turun hingga 15°C di malam hari, dengan kabut dan hujan yang sering turun di sore hari. Kami sarankan membawa jaket. Semua villa dilengkapi pemanas air dan selimut tebal, serta kolam renang air hangat.',
                'translations' => [
                    'en' => ['title' => 'Weather and temperature in Puncak', 'body' => 'Temperatures around the villas are about 18–24°C during the day and can drop to 15°C at night, with mist and afternoon rain common. We recommend bringing a jacket. Every villa has hot water, thick blankets and a heated pool.'],
                    'ja' => ['title' => 'プンチャックの気候と気温', 'body' => 'ヴィラ周辺の気温は日中約18〜24°C、夜は15°Cまで下がることがあり、霧や午後の雨もよくあります。上着のご持参をおすすめします。全ヴィラに給湯設備、厚手の毛布、温水プールを備えています。'],
                ],
                'tags' => ['weather', 'cuaca', 'cold', 'dingin', 'jacket'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Kolam renang air hangat',
                'image_url' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=800&q=80',
                'body' => 'Setiap villa memiliki kolam renang air hangat pribadi yang bisa digunakan kapan saja, nyaman meski udara Puncak sedang dingin. Ada juga kolam renang bersama menghadap kebun teh yang buka setiap hari pukul 07:00–19:00.',
                'translations' => [
                    'en' => ['title' => 'Heated swimming pools', 'body' => 'Every villa has its own heated private pool you can use at any time, comfortable even when the Puncak air is cool. There is also a shared pool overlooking the tea plantation, open daily from 7:00 AM to 7:00 PM.'],
                    'ja' => ['title' => '温水プール', 'body' => '全ヴィラに温水のプライベートプールがあり、プンチャックの涼しい空気の中でもいつでも快適にご利用いただけます。茶畑を望む共用プールも毎日7:00〜19:00に営業しています。'],
                ],
                'tags' => ['pool', 'private pool', 'heated pool', 'swimming pool', 'hours'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Spa dan jalan pagi di kebun teh',
                'image_url' => 'https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=800&q=80',
                'body' => 'Spa buka pukul 10:00–21:00 dengan reservasi terlebih dahulu, dan terapis kami juga bisa datang untuk pijat di villa Anda. Setiap pagi pukul 06:30 ada jalan santai gratis bersama pemandu menyusuri kebun teh di sekitar villa.',
                'translations' => [
                    'en' => ['title' => 'Spa and tea plantation morning walk', 'body' => 'The spa is open from 10:00 AM to 9:00 PM by advance reservation, and our therapists can also come to your villa for a massage. Every morning at 6:30 AM there is a free guided walk through the tea plantation around the villas.'],
                    'ja' => ['title' => 'スパと茶畑の朝散歩', 'body' => 'スパは事前予約制で10:00〜21:00に営業しており、セラピストがヴィラへ伺うマッサージもご利用いただけます。毎朝6:30からは、ヴィラ周辺の茶畑をガイドと歩く無料の散策がございます。'],
                ],
                'tags' => ['spa', 'massage', 'in-villa', 'tea plantation', 'kebun teh', 'walk'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Parkir dan Wi-Fi',
                'image_url' => 'https://images.unsplash.com/photo-1506521781263-d8422e82f27a?auto=format&fit=crop&w=800&q=80',
                'body' => 'Parkir mobil dan motor tersedia gratis bagi tamu menginap, termasuk area untuk minibus rombongan. Wi-Fi gratis tersedia di seluruh villa dan fasilitas umum dengan kecepatan yang memadai untuk video call.',
                'translations' => [
                    'en' => ['title' => 'Parking and Wi-Fi', 'body' => 'Free car and motorbike parking is available for in-house guests, including space for group minibuses. Complimentary Wi-Fi covers every villa and all public areas, fast enough for video calls.'],
                    'ja' => ['title' => '駐車場とWi-Fi', 'body' => '宿泊のお客様は車・バイクの駐車場を無料でご利用いただけ、団体用ミニバスのスペースもございます。全ヴィラおよび共用エリアで無料Wi-Fiをご利用いただけ、ビデオ通話にも十分な速度です。'],
                ],
                'tags' => ['parking', 'wifi'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_DINING,
                'title' => 'Sarapan dan BBQ',
                'image_url' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=800&q=80',
                'body' => 'Sarapan disajikan di Restoran Pakuan pukul 06:30–10:30, dengan menu Sunda dan internasional. Tamu juga bisa memilih sarapan diantar ke villa tanpa biaya tambahan. Paket BBQ malam bisa dipesan untuk villa yang memiliki area BBQ, paling lambat pukul 15:00 di hari yang sama.',
                'translations' => [
                    'en' => ['title' => 'Breakfast and BBQ', 'body' => 'Breakfast is served at Pakuan Restaurant from 6:30 AM to 10:30 AM, with Sundanese and international dishes. Guests can also have breakfast served in their villa at no extra charge. Evening BBQ packages can be ordered for villas with a BBQ area, by 3:00 PM on the same day.'],
                    'ja' => ['title' => '朝食とBBQ', 'body' => '朝食はパクアン・レストランにて6:30〜10:30に提供され、スンダ料理と洋食をお楽しみいただけます。追加料金なしでヴィラでの朝食サービスもご利用いただけます。BBQエリア付きのヴィラでは、当日15:00までのご注文で夕食のBBQパッケージをご利用いただけます。'],
                ],
                'tags' => ['breakfast', 'restaurant', 'in-villa dining', 'bbq'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_POLICIES,
                'title' => 'Kebijakan anak dan tempat tidur tambahan',
                'body' => 'Anak di bawah 5 tahun menginap gratis tanpa tempat tidur tambahan menggunakan fasilitas yang ada. Tempat tidur tambahan (extra bed) tersedia dengan biaya tambahan per malam, tergantung tipe villa, dan harus dipesan sebelum kedatangan karena jumlahnya terbatas.',
                'translations' => [
                    'en' => ['title' => 'Children and extra bed policy', 'body' => 'Children under 5 stay free using existing bedding. An extra bed is available for an additional nightly fee depending on villa type, and should be requested before arrival as availability is limited.'],
                    'ja' => ['title' => 'お子様・エキストラベッドについて', 'body' => '5歳未満のお子様は既存の寝具を利用する場合、無料でご宿泊いただけます。エキストラベッドはヴィラタイプに応じて追加料金でご利用いただけますが、数に限りがあるため到着前のご予約をお願いします。'],
                ],
                'tags' => ['children', 'extra bed', 'kids'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_POLICIES,
                'title' => 'Kebijakan pembatalan',
                'body' => 'Pembatalan gratis hingga 3 hari sebelum tanggal check-in. Pembatalan dalam 3 hari terakhir dikenakan biaya satu malam pertama. Tidak hadir tanpa pemberitahuan (no-show) dikenakan biaya penuh sesuai lama menginap yang dipesan.',
                'translations' => [
                    'en' => ['title' => 'Cancellation policy', 'body' => 'Free cancellation up to 3 days before check-in. Cancellations within 3 days of check-in are charged the first night. A no-show is charged the full booked stay.'],
                    'ja' => ['title' => 'キャンセルポリシー', 'body' => 'チェックインの3日前まで無料でキャンセルいただけます。3日以内のキャンセルは1泊分の料金が発生します。無連絡不泊（ノーショー）の場合は、ご予約いただいた宿泊期間分の全額が請求されます。'],
                ],
                'tags' => ['cancellation', 'refund', 'no-show'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_TRANSPORT,
                'title' => 'Antar-jemput dari Jakarta dan bandara',
                'image_url' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',
                'body' => 'Layanan antar-jemput tersedia dengan biaya tambahan dari Bandara Soekarno-Hatta (sekitar 2–3 jam), Bandara Halim Perdanakusuma (sekitar 1,5–2 jam), dan Stasiun Bogor (sekitar 1 jam), tergantung kondisi lalu lintas Puncak. Pemesanan minimal 24 jam sebelum kedatangan.',
                'translations' => [
                    'en' => ['title' => 'Transfers from Jakarta and the airports', 'body' => 'Transfers are available for an extra fee from Soekarno-Hatta Airport (about 2–3 hours), Halim Perdanakusuma Airport (about 1.5–2 hours) and Bogor Station (about 1 hour), depending on Puncak traffic. Please book at least 24 hours before arrival.'],
                    'ja' => ['title' => 'ジャカルタ・空港からの送迎', 'body' => 'スカルノ・ハッタ国際空港（約2〜3時間）、ハリム・ペルダナクスマ空港（約1.5〜2時間）、ボゴール駅（約1時間）からの送迎サービスを追加料金にてご利用いただけます。所要時間はプンチャックの交通状況により異なります。到着の24時間前までにご予約ください。'],
                ],
                'tags' => ['airport transfer', 'soekarno-hatta', 'halim', 'jakarta', 'stasiun bogor', 'transport'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_TRANSPORT,
                'title' => 'Atraksi terdekat',
                'image_url' => 'https://images.unsplash.com/photo-1433086966358-54859d0ed716?auto=format&fit=crop&w=800&q=80',
                'body' => 'Taman Safari Indonesia sekitar 10 menit berkendara, Kebun Teh Gunung Mas sekitar 15 menit, Telaga Warna dan Curug Cilember masing-masing sekitar 20 menit. Kebun Raya Bogor dapat dicapai sekitar 1 jam berkendara.',
                'translations' => [
                    'en' => ['title' => 'Nearby attractions', 'body' => 'Taman Safari Indonesia is about a 10-minute drive, Gunung Mas Tea Plantation about 15 minutes, and Telaga Warna lake and Cilember Waterfall about 20 minutes each. The Bogor Botanical Gardens are about an hour away by car.'],
                    'ja' => ['title' => '近隣の観光スポット', 'body' => 'タマン・サファリ・インドネシアまで車で約10分、グヌン・マス茶園まで約15分、テラガ・ワルナ湖とチレンブル滝まではそれぞれ約20分です。ボゴール植物園へは車で約1時間です。'],
                ],
                'tags' => ['attractions', 'taman safari', 'kebun teh', 'telaga warna', 'curug', 'kebun raya bogor'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FAQ,
                'title' => 'Apakah semua villa memiliki bathtub?',
                'body' => 'Tidak semua. One Bedroom Garden Pool Villa menggunakan shower air hangat, sedangkan One Bedroom Mountain View Villa, Two Bedroom Family Pool Villa, dan Three Bedroom Grand Pool Villa dilengkapi bathtub.',
                'translations' => [
                    'en' => ['title' => 'Do all villas have a bathtub?', 'body' => 'Not all of them. The One Bedroom Garden Pool Villa has a hot shower only, while the One Bedroom Mountain View Villa, Two Bedroom Family Pool Villa, and Three Bedroom Grand Pool Villa come with a bathtub.'],
                    'ja' => ['title' => '全てのヴィラにバスタブはありますか？', 'body' => '全ヴィラではありません。ワンベッドルーム ガーデンプールヴィラは温水シャワーのみですが、ワンベッドルーム マウンテンビューヴィラ、ツーベッドルーム ファミリープールヴィラ、スリーベッドルーム グランドプールヴィラにはバスタブが付いています。'],
                ],
                'tags' => ['bathtub', 'bathroom', 'faq'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FAQ,
                'title' => 'Apakah villa menerima hewan peliharaan?',
                'body' => 'Untuk saat ini kami belum dapat menerima hewan peliharaan, kecuali hewan pemandu bagi penyandang disabilitas.',
                'translations' => [
                    'en' => ['title' => 'Are pets allowed?', 'body' => 'We are currently unable to accommodate pets, with the exception of service animals for guests with disabilities.'],
                    'ja' => ['title' => 'ペットは同伴できますか？', 'body' => '現在、障がいをお持ちのお客様の補助犬を除き、ペットの同伴はご遠慮いただいております。'],
                ],
                'tags' => ['pets', 'faq'],
            ],
        ];

        $sort = 0;
        foreach ($items as $item) {
            $villa->knowledgeItems()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'category' => $item['category'],
                    'body' => $item['body'],
                    'translations' => $item['translations'],
                    'tags' => $item['tags'],
                    'image_url' => $item['image_url'] ?? null,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]
            );
        }
    }
}
