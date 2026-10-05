<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentVilla;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Reservation\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    use ResolvesCurrentVilla;

    public function __construct(private readonly ReservationService $reservations) {}

    public function index(Request $request): View
    {
        $villa = $this->currentVilla($request);

        $status = in_array($request->query('status'), [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED], true)
            ? $request->query('status')
            : null;

        $bookings = $villa->bookings()
            ->with('unitType')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', compact('villa', 'bookings', 'status'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $villa = $this->currentVilla($request);
        abort_if($booking->villa_id !== $villa->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', [Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED])],
        ]);

        if ($data['status'] === Booking::STATUS_CANCELLED && $booking->status !== Booking::STATUS_CANCELLED) {
            $this->reservations->releaseInventory($booking);
        }

        $booking->update(['status' => $data['status']]);

        return back()->with('status', "Booking {$booking->reference} marked as {$data['status']}.");
    }
}
