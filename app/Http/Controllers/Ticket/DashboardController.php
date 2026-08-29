<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $stats = [
            'total'       => Ticket::where('company_id', $companyId)->count(),
            'open'        => Ticket::where('company_id', $companyId)->where('status', 'open')->count(),
            'in_progress' => Ticket::where('company_id', $companyId)->where('status', 'in-progress')->count(),
            'resolved'    => Ticket::where('company_id', $companyId)->whereIn('status', ['resolved', 'closed'])->count(),
            'critical'    => Ticket::where('company_id', $companyId)->where('severity', 'critical')->count(),
            'high'        => Ticket::where('company_id', $companyId)->where('severity', 'high')->count(),
        ];

        $recentTickets = Ticket::with(['assignee', 'reporter'])
            ->where('company_id', $companyId)
            ->latest()
            ->take(8)
            ->get();

        return view('pages.tickets.dashboard', compact('stats', 'recentTickets'));
    }
}
