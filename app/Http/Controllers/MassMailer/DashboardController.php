<?php

namespace App\Http\Controllers\MassMailer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MassMailerContact;
use App\Models\MassMailerTag;
use App\Models\MassMailerCampaign;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_contacts' => MassMailerContact::count(['id']),
            'total_tags' => MassMailerTag::count(['id']),
            'total_campaigns' => MassMailerCampaign::count(['id']),
            'active_campaigns' => MassMailerCampaign::where('status', '=', 'sending')->count(['id']),
        ];

        $recent_campaigns = MassMailerCampaign::latest('id')->take(5)->get();

        return view('pages.mass_mailer.dashboard', compact('stats', 'recent_campaigns'));
    }
}
