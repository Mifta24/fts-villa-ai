<?php

namespace Database\Seeders;

use App\Models\Villa;
use App\Models\VillaKnowledgeItem;
use App\Models\UnitInventory;
use App\Models\UnitType;
use App\Models\User;
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

        $villa = Villa::firstOrCreate(
            ['name' => 'FTS Villa AI'],
            [
                'slug' => Villa::generateUniqueSlug('FTS Villa AI'),
                'description' => 'Kompleks villa privat tepi pantai di Nusa Dua, Bali. Setiap villa punya kolam renang pribadi, dengan akses jalan kaki langsung ke pantai.',
                'translations' => [
                    'id' => [
                        'description' => 'Kompleks villa privat tepi pantai di Nusa Dua, Bali. Setiap villa punya kolam renang pribadi, dengan akses jalan kaki langsung ke pantai.',
                    ],
                    'en' => [
                        'description' => 'A beachfront collection of private villas in Nusa Dua, Bali. Every villa has its own pool, with direct walking access to the beach.',
                    ],
                    'ja' => [
                        'description' => 'バリ島ヌサドゥアのビーチフロントに佇むプライベートヴィラリゾート。全ヴィラにプライベートプールを備え、ビーチへ徒歩でアクセスできます。',
                    ],
                ],
                'address' => 'Jl. Pantai Mengiat No. 8, Nusa Dua',
                'city' => 'Bali',
                'country' => 'Indonesia',
                'latitude' => -8.8008,
                'longitude' => 115.2317,
                'phone' => '+62 361 771234',
                'whatsapp' => '6281234567890',
                'email' => 'reservation@ftsvilla.test',
                'timezone' => 'Asia/Makassar',
                'currency' => 'IDR',
                'default_locale' => 'id',
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'cover_path' => 'https://images.unsplash.com/photo-1582610116397-edb318620f90?auto=format&fit=crop&w=1920&q=80',
                'public_status' => 'published',
            ]
        );

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
                'description' => 'Villa satu kamar tidur dengan kolam renang pribadi dan taman tropis yang tenang, cocok untuk pasangan atau keluarga kecil.',
                'translations' => [
                    'id' => ['name' => 'One Bedroom Garden Pool Villa', 'description' => 'Villa satu kamar tidur dengan kolam renang pribadi dan taman tropis yang tenang, cocok untuk pasangan atau keluarga kecil.'],
                    'en' => ['name' => 'One Bedroom Garden Pool Villa', 'description' => 'A one-bedroom villa with its own private pool and a quiet tropical garden, ideal for couples or small families.'],
                    'ja' => ['name' => 'ワンベッドルーム ガーデンプールヴィラ', 'description' => 'プライベートプールと静かな熱帯庭園を備えたワンベッドルームのヴィラで、カップルや小さなご家族に最適です。'],
                ],
                'size_sqm' => 120,
                'max_adults' => 2,
                'max_children' => 1,
                'bed_config' => [['type' => 'king', 'count' => 1]],
                'view_type' => 'garden',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 350000,
                'base_price' => 2500000,
                'amenities' => ['private_pool', 'air_conditioning', 'wifi', 'minibar', 'safe_deposit_box', 'outdoor_shower', 'gazebo', 'coffee_maker'],
                'total_units' => 8,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?auto=format&fit=crop&w=1200&q=80', 'tags' => ['pool', 'view'], 'alt' => 'Kolam renang pribadi Garden Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur king Garden Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom'], 'alt' => 'Kamar mandi Garden Pool Villa'],
                ],
            ],
            [
                'slug' => 'one-bedroom-ocean-pool-villa',
                'name' => 'One Bedroom Ocean Pool Villa',
                'description' => 'Villa satu kamar tidur dengan kolam renang pribadi menghadap laut lepas dan bathtub di kamar mandi terbuka.',
                'translations' => [
                    'id' => ['name' => 'One Bedroom Ocean Pool Villa', 'description' => 'Villa satu kamar tidur dengan kolam renang pribadi menghadap laut lepas dan bathtub di kamar mandi terbuka.'],
                    'en' => ['name' => 'One Bedroom Ocean Pool Villa', 'description' => 'A one-bedroom villa with a private pool facing the open ocean and a bathtub in an open-air bathroom.'],
                    'ja' => ['name' => 'ワンベッドルーム オーシャンプールヴィラ', 'description' => '海を望むプライベートプールと、開放的なバスルームのバスタブを備えたワンベッドルームのヴィラです。'],
                ],
                'size_sqm' => 150,
                'max_adults' => 2,
                'max_children' => 1,
                'bed_config' => [['type' => 'king', 'count' => 1]],
                'view_type' => 'ocean',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 350000,
                'base_price' => 3600000,
                'amenities' => ['private_pool', 'air_conditioning', 'wifi', 'minibar', 'safe_deposit_box', 'bathtub', 'gazebo', 'coffee_maker'],
                'total_units' => 6,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80', 'tags' => ['pool', 'view'], 'alt' => 'Kolam pribadi menghadap laut'],
                    ['url' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur Ocean Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom', 'bathtub'], 'alt' => 'Kamar mandi dengan bathtub'],
                ],
            ],
            [
                'slug' => 'two-bedroom-family-pool-villa',
                'name' => 'Two Bedroom Family Pool Villa',
                'description' => 'Villa dua kamar tidur dengan ruang keluarga, dapur lengkap, dan kolam renang pribadi, ideal untuk keluarga dengan anak.',
                'translations' => [
                    'id' => ['name' => 'Two Bedroom Family Pool Villa', 'description' => 'Villa dua kamar tidur dengan ruang keluarga, dapur lengkap, dan kolam renang pribadi, ideal untuk keluarga dengan anak.'],
                    'en' => ['name' => 'Two Bedroom Family Pool Villa', 'description' => 'A two-bedroom villa with a living room, a full kitchen and a private pool, ideal for families with children.'],
                    'ja' => ['name' => 'ツーベッドルーム ファミリープールヴィラ', 'description' => 'リビングルーム、フルキッチン、プライベートプールを備えたツーベッドルームのヴィラで、お子様連れのご家族に最適です。'],
                ],
                'size_sqm' => 260,
                'max_adults' => 4,
                'max_children' => 2,
                'bed_config' => [['type' => 'king', 'count' => 1], ['type' => 'twin', 'count' => 2]],
                'view_type' => 'garden',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 400000,
                'base_price' => 5800000,
                'amenities' => ['private_pool', 'living_room', 'kitchen', 'air_conditioning', 'wifi', 'safe_deposit_box', 'bathtub', 'gazebo'],
                'total_units' => 4,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1590073844006-33379778ae09?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bedroom'], 'alt' => 'Kamar tidur utama Family Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80', 'tags' => ['living_room'], 'alt' => 'Ruang keluarga Family Pool Villa'],
                    ['url' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?auto=format&fit=crop&w=1200&q=80', 'tags' => ['bathroom', 'bathtub'], 'alt' => 'Kamar mandi Family Pool Villa'],
                ],
            ],
            [
                'slug' => 'three-bedroom-grand-pool-villa',
                'name' => 'Three Bedroom Grand Pool Villa',
                'description' => 'Villa tiga kamar tidur terluas kami dengan kolam renang infinity pribadi menghadap laut, dapur lengkap, dan layanan butler — cocok untuk rombongan atau keluarga besar.',
                'translations' => [
                    'id' => ['name' => 'Three Bedroom Grand Pool Villa', 'description' => 'Villa tiga kamar tidur terluas kami dengan kolam renang infinity pribadi menghadap laut, dapur lengkap, dan layanan butler — cocok untuk rombongan atau keluarga besar.'],
                    'en' => ['name' => 'Three Bedroom Grand Pool Villa', 'description' => 'Our largest villa, with three bedrooms, a private ocean-facing infinity pool, a full kitchen and butler service — made for groups and larger families.'],
                    'ja' => ['name' => 'スリーベッドルーム グランドプールヴィラ', 'description' => '海を望むプライベートインフィニティプール、フルキッチン、バトラーサービスを備えた最も広いスリーベッドルームのヴィラで、グループや大人数のご家族に最適です。'],
                ],
                'size_sqm' => 400,
                'max_adults' => 6,
                'max_children' => 2,
                'bed_config' => [['type' => 'king', 'count' => 2], ['type' => 'twin', 'count' => 2]],
                'view_type' => 'ocean',
                'breakfast_included' => true,
                'extra_bed_available' => true,
                'extra_bed_price' => 400000,
                'base_price' => 8900000,
                'amenities' => ['private_pool', 'living_room', 'kitchen', 'butler_service', 'air_conditioning', 'wifi', 'safe_deposit_box', 'bathtub', 'outdoor_shower'],
                'total_units' => 2,
                'images' => [
                    ['url' => 'https://images.unsplash.com/photo-1582610116397-edb318620f90?auto=format&fit=crop&w=1200&q=80', 'tags' => ['pool', 'view'], 'alt' => 'Kolam infinity Grand Pool Villa'],
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
                'body' => 'FTS Villa AI adalah kompleks 20 villa privat tepi pantai di Nusa Dua, terdiri dari empat tipe villa satu hingga tiga kamar tidur. Setiap villa memiliki kolam renang pribadi, dan tamu dapat berjalan kaki langsung ke pantai pasir putih.',
                'translations' => [
                    'en' => ['title' => 'About FTS Villa AI', 'body' => 'FTS Villa AI is a beachfront collection of 20 private villas in Nusa Dua, in four villa types from one to three bedrooms. Every villa has its own private pool, and guests can walk straight to the white-sand beach.'],
                    'ja' => ['title' => 'FTS Villa AIについて', 'body' => 'FTS Villa AIはヌサドゥアのビーチフロントに建つ20棟のプライベートヴィラで、ワンベッドルームからスリーベッドルームまで4タイプをご用意しています。全ヴィラにプライベートプールがあり、白砂のビーチへ徒歩でアクセスできます。'],
                ],
                'tags' => ['overview', 'introduction'],
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
                'title' => 'Late check-in setelah tengah malam',
                'body' => 'Resepsionis villa kami buka 24 jam, jadi tamu dengan penerbangan yang tiba larut malam tetap dapat check-in kapan saja. Staf kami akan mengantar Anda ke villa. Mohon informasikan perkiraan waktu tiba agar villa sudah siap.',
                'translations' => [
                    'en' => ['title' => 'Late-night check-in', 'body' => 'Our villa reception is open 24 hours, so guests arriving on a late flight can still check in at any time, and our staff will walk you to your villa. Please let us know your estimated arrival time so your villa is ready.'],
                    'ja' => ['title' => '深夜チェックインについて', 'body' => 'レセプションは24時間対応しておりますので、深夜到着の便をご利用のお客様もいつでもチェックインいただけます。スタッフがヴィラまでご案内します。到着予定時刻を事前にお知らせください。'],
                ],
                'tags' => ['late check-in', 'flight', 'midnight', 'front desk'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Kolam renang',
                'image_url' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?auto=format&fit=crop&w=800&q=80',
                'body' => 'Setiap villa memiliki kolam renang pribadi yang bisa digunakan kapan saja. Selain itu, kolam renang bersama menghadap laut di beach club buka setiap hari pukul 07:00–19:00, dengan handuk gratis di pool bar.',
                'translations' => [
                    'en' => ['title' => 'Swimming pool', 'body' => 'Every villa has its own private pool you can use at any time. There is also a shared ocean-facing pool at the beach club, open daily from 7:00 AM to 7:00 PM, with free towels at the pool bar.'],
                    'ja' => ['title' => 'スイミングプール', 'body' => '全ヴィラにプライベートプールがあり、いつでもご利用いただけます。ビーチクラブには海に面した共用プールもあり、毎日7:00〜19:00に営業しています。タオルはプールバーで無料でご利用いただけます。'],
                ],
                'tags' => ['pool', 'private pool', 'swimming pool', 'hours'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Gym, spa, dan in-villa massage',
                'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
                'body' => 'Pusat kebugaran buka 24 jam untuk tamu menginap. Spa buka pukul 10:00–21:00 dengan reservasi terlebih dahulu, dan terapis kami juga bisa datang untuk pijat di villa Anda.',
                'translations' => [
                    'en' => ['title' => 'Gym, spa, and in-villa massage', 'body' => 'The fitness center is open 24 hours for in-house guests. The spa is open from 10:00 AM to 9:00 PM by advance reservation, and our therapists can also come to your villa for a massage.'],
                    'ja' => ['title' => 'ジム・スパ・ヴィラ内マッサージ', 'body' => 'フィットネスセンターは宿泊のお客様に24時間ご利用いただけます。スパは事前予約制で10:00〜21:00に営業しており、セラピストがヴィラへ伺うマッサージもご利用いただけます。'],
                ],
                'tags' => ['gym', 'spa', 'massage', 'in-villa'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FACILITIES,
                'title' => 'Parkir dan Wi-Fi',
                'image_url' => 'https://images.unsplash.com/photo-1506521781263-d8422e82f27a?auto=format&fit=crop&w=800&q=80',
                'body' => 'Parkir mobil dan motor tersedia gratis bagi tamu menginap. Wi-Fi gratis tersedia di seluruh villa dan fasilitas umum dengan kecepatan yang memadai untuk video call.',
                'translations' => [
                    'en' => ['title' => 'Parking and Wi-Fi', 'body' => 'Free car and motorbike parking is available for in-house guests. Complimentary Wi-Fi covers every villa and all public areas, fast enough for video calls.'],
                    'ja' => ['title' => '駐車場とWi-Fi', 'body' => '宿泊のお客様は車・バイクの駐車場を無料でご利用いただけます。全ヴィラおよび共用エリアで無料Wi-Fiをご利用いただけ、ビデオ通話にも十分な速度です。'],
                ],
                'tags' => ['parking', 'wifi'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_DINING,
                'title' => 'Sarapan',
                'image_url' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=800&q=80',
                'body' => 'Sarapan disajikan di Restoran Samudra pukul 06:30–10:30, mencakup menu Indonesia dan internasional. Tamu juga bisa memilih sarapan diantar dan disajikan di villa tanpa biaya tambahan. Sarapan sudah termasuk pada semua tipe villa kecuali disebutkan lain.',
                'translations' => [
                    'en' => ['title' => 'Breakfast', 'body' => 'Breakfast is served at Samudra Restaurant from 6:30 AM to 10:30 AM, with both Indonesian and international dishes. Guests can also have breakfast served in their villa at no extra charge. Breakfast is included with every villa type unless stated otherwise.'],
                    'ja' => ['title' => '朝食について', 'body' => '朝食はサムドラ・レストランにて6:30〜10:30に提供され、インドネシア料理と洋食の両方をお楽しみいただけます。追加料金なしでヴィラでの朝食サービスもご利用いただけます。特に記載がない限り、全てのヴィラタイプに朝食が含まれます。'],
                ],
                'tags' => ['breakfast', 'restaurant', 'in-villa dining'],
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
                'title' => 'Antar-jemput bandara',
                'image_url' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',
                'body' => 'Layanan antar-jemput dari dan ke Bandara Internasional Ngurah Rai tersedia dengan biaya tambahan, sekitar 30–40 menit perjalanan. Pemesanan harus dilakukan minimal 24 jam sebelum kedatangan dengan mengirimkan nomor penerbangan.',
                'translations' => [
                    'en' => ['title' => 'Airport transfer', 'body' => 'Airport transfer to and from Ngurah Rai International Airport is available for an extra fee, about a 30-40 minute drive. Please book at least 24 hours in advance and share your flight number.'],
                    'ja' => ['title' => '空港送迎', 'body' => 'ングラライ国際空港との送迎サービスを追加料金にてご利用いただけます（所要時間約30〜40分）。到着の24時間前までにフライト番号とあわせてご予約ください。'],
                ],
                'tags' => ['airport transfer', 'ngurah rai', 'transport'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_TRANSPORT,
                'title' => 'Atraksi terdekat',
                'image_url' => 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80',
                'body' => 'Pantai Nusa Dua dapat dicapai dengan berjalan kaki 2 menit. Water Blow Nusa Dua sekitar 5 menit berkendara, dan Pura Uluwatu berjarak sekitar 45 menit berkendara ke arah selatan.',
                'translations' => [
                    'en' => ['title' => 'Nearby attractions', 'body' => 'Nusa Dua Beach is a 2-minute walk away. Water Blow Nusa Dua is about a 5-minute drive, and Uluwatu Temple is roughly a 45-minute drive to the south.'],
                    'ja' => ['title' => '近隣の観光スポット', 'body' => 'ヌサドゥア・ビーチまで徒歩2分。ウォーターブロウ・ヌサドゥアまで車で約5分、ウルワツ寺院までは南へ車で約45分です。'],
                ],
                'tags' => ['attractions', 'nusa dua beach', 'uluwatu', 'water blow'],
            ],
            [
                'category' => VillaKnowledgeItem::CATEGORY_FAQ,
                'title' => 'Apakah semua villa memiliki bathtub?',
                'body' => 'Tidak semua. One Bedroom Garden Pool Villa menggunakan shower terbuka, sedangkan One Bedroom Ocean Pool Villa, Two Bedroom Family Pool Villa, dan Three Bedroom Grand Pool Villa dilengkapi bathtub.',
                'translations' => [
                    'en' => ['title' => 'Do all villas have a bathtub?', 'body' => 'Not all of them. The One Bedroom Garden Pool Villa has an outdoor shower only, while the One Bedroom Ocean Pool Villa, Two Bedroom Family Pool Villa, and Three Bedroom Grand Pool Villa come with a bathtub.'],
                    'ja' => ['title' => '全てのヴィラにバスタブはありますか？', 'body' => '全室ではありません。デラックス ガーデンビューはシャワーのみですが、デラックス オーシャンビュー、ファミリースイート、ハネムーン プールヴィラにはバスタブが付いています。'],
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
