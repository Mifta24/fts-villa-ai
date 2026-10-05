<?php

namespace App\Services\Reservation;

use App\Models\Booking;
use App\Models\Villa;

/**
 * Builds the WhatsApp / phone / email hand-over links that carry a
 * reservation summary (or a plain "talk to staff" request) to the villa team.
 */
class ReservationHandover
{
    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forBooking(Villa $villa, Booking $booking, string $locale): array
    {
        $message = $this->reservationMessage($booking, $locale);

        return $this->links($villa, $message, $this->subject('reservation', $locale).' '.$booking->reference);
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    public function forStaff(Villa $villa, string $locale): array
    {
        return $this->links($villa, $this->subject('staff_message', $locale), $this->subject('staff', $locale));
    }

    /**
     * @return array{whatsapp_url: ?string, phone_url: ?string, email_url: ?string}
     */
    private function links(Villa $villa, string $message, string $subject): array
    {
        $whatsapp = preg_replace('/\D+/', '', (string) $villa->whatsapp);
        $phone = preg_replace('/[^\d+]/', '', (string) $villa->phone);

        return [
            'whatsapp_url' => $whatsapp !== '' ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : null,
            'phone_url' => $phone !== '' ? 'tel:'.$phone : null,
            'email_url' => filled($villa->email) ? 'mailto:'.$villa->email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($message) : null,
        ];
    }

    private function reservationMessage(Booking $booking, string $locale): string
    {
        $booking->loadMissing('unitType');

        $labels = match ($locale) {
            'en' => ['Hello, I would like to request a reservation.', 'Name', 'Check-in', 'Check-out', 'Guests', 'Villas', 'Villa Type', 'Special Request', 'Reference'],
            'ja' => ['こんにちは。宿泊のご予約をリクエストしたいです。', 'お名前', 'チェックイン', 'チェックアウト', '人数', 'ヴィラ数', 'ヴィラタイプ', 'ご要望', '予約番号'],
            default => ['Halo, saya ingin mengajukan reservasi.', 'Nama', 'Check-in', 'Check-out', 'Tamu', 'Jumlah Villa', 'Tipe Villa', 'Permintaan Khusus', 'Referensi'],
        };

        $guests = $booking->adults + $booking->children;

        return implode("\n", [
            $labels[0],
            '',
            "{$labels[1]}: {$booking->guest_name}",
            "{$labels[2]}: ".$booking->check_in->locale($locale)->translatedFormat('j F Y'),
            "{$labels[3]}: ".$booking->check_out->locale($locale)->translatedFormat('j F Y'),
            "{$labels[4]}: {$guests}",
            "{$labels[5]}: {$booking->unit_count}",
            "{$labels[6]}: ".$booking->unitType->translatedName($locale),
            "{$labels[7]}: ".($booking->notes ?: '-'),
            '',
            "{$labels[8]}: {$booking->reference}",
        ]);
    }

    private function subject(string $key, string $locale): string
    {
        return [
            'en' => ['reservation' => 'Reservation request', 'staff' => 'Question for villa staff', 'staff_message' => 'Hello, I would like to speak with villa staff.'],
            'ja' => ['reservation' => '予約リクエスト', 'staff' => 'スタッフへのご相談', 'staff_message' => 'こんにちは。ヴィラのスタッフとお話ししたいです。'],
            'id' => ['reservation' => 'Permintaan reservasi', 'staff' => 'Pertanyaan untuk staf villa', 'staff_message' => 'Halo, saya ingin bicara dengan staf villa.'],
        ][$locale][$key];
    }
}
