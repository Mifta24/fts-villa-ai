<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentVilla;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\HandoverRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentVilla;

    public function index(Request $request): View
    {
        $villa = $this->currentVilla($request);

        $stats = [
            'unit_types' => $villa->unitTypes()->count(),
            'knowledge_items' => $villa->knowledgeItems()->count(),
            'pending_bookings' => $villa->bookings()->where('status', Booking::STATUS_PENDING)->count(),
            'open_handovers' => HandoverRequest::whereHas('conversation', fn ($q) => $q->where('villa_id', $villa->id))
                ->where('status', HandoverRequest::STATUS_OPEN)
                ->count(),
        ];

        $recentBookings = $villa->bookings()->latest()->take(5)->with('unitType')->get();

        $openHandovers = HandoverRequest::whereHas('conversation', fn ($q) => $q->where('villa_id', $villa->id))
            ->where('status', HandoverRequest::STATUS_OPEN)
            ->latest()
            ->take(5)
            ->with('conversation')
            ->get();

        return view('admin.dashboard', [
            'villa' => $villa,
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'openHandovers' => $openHandovers,
        ]);
    }
}
